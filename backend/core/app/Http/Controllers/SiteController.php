<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\BusinessPackage;
use App\Models\Favor;
use App\Models\FreeDelivery;
use App\Models\Frontend;
use App\Models\Gateway;
use App\Models\Language;
use App\Models\LandingServiceRequest;
use App\Models\Page;
use App\Models\Product;
use App\Models\Ride;
use App\Models\RideQueue;
use App\Models\Service;
use App\Models\Store;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Support\DeliveryPricing;
use Laravel\Sanctum\PersonalAccessToken;

class SiteController extends Controller
{
    public function apiDocs()
    {
        $pageTitle = 'Documentación de API Lizto Delivery & Taxi';
        return view('api_docs', compact('pageTitle'));
    }

    public function index()
    {
        $pageTitle = __('Home');
        $sections    = Page::where('tempname', activeTemplate())->where('slug', '/')->first();
        $seoContents = $sections?->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $taxiServices = Service::active()->take(4)->get();
        $storeCount = Schema::hasTable('stores') ? Store::active()->count() : 0;

        return view('Template::home', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'taxiServices', 'storeCount'));
    }

    public function serviceRequestSubmit(Request $request)
    {
        $isJson = $request->expectsJson() || $request->isJson() || $request->wantsJson();

        $validated = $request->validate([
            'service_type'    => 'required|in:taxi,delivery',
            'name'            => 'required|string|max:120',
            'phone'           => 'required|string|max:30',
            'pickup'          => 'required|string|max:255',
            'destination'     => 'required|string|max:255',
            'notes'           => 'nullable|string|max:500',
            'service_id'      => 'nullable|integer',
            'pickup_lat'      => 'nullable|numeric',
            'pickup_lng'      => 'nullable|numeric',
            'destination_lat' => 'nullable|numeric',
            'destination_lng' => 'nullable|numeric',
            'store_id'        => 'nullable|exists:stores,id',
            'delivery_lat'    => 'nullable|numeric',
            'delivery_lng'    => 'nullable|numeric',
            'cart_payload'    => 'nullable|string|max:10000',
        ]);

        $cart = null;
        if (!empty($validated['cart_payload'])) {
            $submittedCart = json_decode($validated['cart_payload'], true);
            if (!is_array($submittedCart) || count($submittedCart) === 0) {
                return back()->withErrors(['cart_payload' => 'Agrega al menos un producto al carrito.'])->withInput();
            }

            $cart = collect($submittedCart)->map(function ($item) use ($validated) {
                $product = Product::active()
                    ->where('store_id', $validated['store_id'] ?? 0)
                    ->find($item['product_id'] ?? 0);
                $quantity = max(1, min(99, (int) ($item['quantity'] ?? 1)));

                return $product ? [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'quantity'   => $quantity,
                    'unit_price' => $product->finalPrice(),
                ] : null;
            })->filter()->values()->all();

            if (count($cart) === 0) {
                return back()->withErrors(['cart_payload' => 'Los productos seleccionados ya no están disponibles.'])->withInput();
            }
        }

        unset($validated['cart_payload']);

        $serviceRequest = LandingServiceRequest::create($validated + [
            'ip_address' => $request->ip(),
            'status'     => 'pending',
            'cart_json'  => $cart,
        ]);

        // Save user's pickup location for emergency/audit purposes
        if (auth()->check() && !empty($validated['pickup_lat']) && !empty($validated['pickup_lng'])) {
            auth()->user()->update([
                'latitude'  => $validated['pickup_lat'],
                'longitude' => $validated['pickup_lng'],
            ]);
        }

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = 0;
        $adminNotification->title = substr(sprintf(
            'Nueva solicitud web de %s: %s - %s',
            $serviceRequest->service_type === 'taxi' ? 'taxi' : 'delivery',
            $serviceRequest->name,
            $serviceRequest->phone
        ), 0, 255);
        $adminNotification->save();

        // For taxi: create a real Ride + queue for driver notification
        $rideData = null;
        if ($validated['service_type'] === 'taxi' && $validated['service_id'] && $validated['pickup_lat'] && $validated['destination_lat']) {
            $service = Service::active()->find($validated['service_id']);
            if ($service) {
                // Find zones
                $pickupAddr = ['lat' => $validated['pickup_lat'], 'long' => $validated['pickup_lng']];
                $destAddr = ['lat' => $validated['destination_lat'], 'long' => $validated['destination_lng']];
                $zones = Zone::active()->get();
                $pickupZone = null;
                $destZone = null;
                foreach ($zones as $z) {
                    if (insideZone($pickupAddr, $z)) { $pickupZone = $z; break; }
                }
                foreach ($zones as $z) {
                    if (insideZone($destAddr, $z)) { $destZone = $z; break; }
                }

                $isCity = $pickupZone && $destZone && $pickupZone->id === $destZone->id;
                $baseFare = (float) ($isCity ? $service->city_base_fare : $service->intercity_base_fare);
                $minTrip = (float) ($isCity ? $service->city_min_trip_fare : $service->intercity_min_trip_fare);
                $rate = (float) ($isCity
                    ? ($service->city_rate_per_km ?: $service->city_recommend_fare ?: 1.60)
                    : ($service->intercity_rate_per_km ?: $service->intercity_recommend_fare ?: 1.60));
                $commissionPct = (float) ($isCity ? $service->city_fare_commission : $service->intercity_fare_commission);
                $rideType = $isCity ? Status::CITY_RIDE : Status::INTER_CITY_RIDE;
                $distance = (float) ($request->distance ?? 0);
                $amount = max($baseFare + ($rate * $distance), $minTrip);

                $ride = Ride::create([
                    'uid'                   => getTrx(10),
                    'user_id'               => auth()->id(),
                    'service_id'            => $service->id,
                    'pickup_zone_id'        => $pickupZone?->id,
                    'destination_zone_id'   => $destZone?->id,
                    'pickup_location'       => $validated['pickup'],
                    'pickup_latitude'       => $validated['pickup_lat'],
                    'pickup_longitude'      => $validated['pickup_lng'],
                    'destination'           => $validated['destination'],
                    'destination_latitude'  => $validated['destination_lat'],
                    'destination_longitude' => $validated['destination_lng'],
                    'distance'              => $distance,
                    'duration'              => $request->duration ?? 0,
                    'amount'                => $amount,
                    'min_amount'            => $amount,
                    'max_amount'            => $amount * 1.2,
                    'recommend_amount'      => $amount,
                    'commission_percentage' => $commissionPct,
                    'ride_type'             => $rideType,
                    'number_of_passenger'   => 1,
                    'payment_type'          => Status::PAYMENT_TYPE_CASH,
                    'status'                => Status::RIDE_PENDING,
                    'note'                  => $validated['notes'] ?? null,
                ]);

                $queue = RideQueue::create([
                    'ride_id'     => $ride->id,
                    'action_type' => 'new_driver_notification',
                    'ordering'    => time(),
                    'dispatch_count' => 1,
                ]);

                // Envío inmediato
                (new \App\Lib\ManageRideQueue())->initQueue($queue);

                $rideData = ['ride_id' => $ride->id, 'ride_uid' => $ride->uid, 'amount' => $amount];
            }
        }

        $notify[] = ['success', __('Solicitud recibida. Te contactaremos en breve para confirmar el servicio.')];

        if ($isJson || $request->ajax()) {
            return response()->json([
                'remark'  => 'success',
                'message' => ['Solicitud recibida. Buscando conductor...'],
                'data'    => [
                    'id'       => $serviceRequest->id,
                    'status'   => $serviceRequest->status,
                    'ride'     => $rideData,
                ],
            ]);
        }

        return back()->withNotify($notify);
    }

    // ── Favor (Lizto Favor) ──

    public function favorPage(Request $request)
    {
        $pageTitle = 'Lizto Favor - Envíos y Encargos';
        $isDeliveryRoute = false;
        $gateways = Gateway::active()->automatic()->get()->map(fn($gw) => [
            'code' => $gw->code,
            'name' => $gw->name,
            'image' => getImage(getFilePath('gateway') . '/' . $gw->image),
            'description' => $gw->description,
        ]);

        return view('Template::delivery.favor', compact('pageTitle', 'isDeliveryRoute', 'gateways'));
    }

    public function favorCreate(Request $request)
    {
        $request->validate([
            'type'              => 'required|in:buy,send',
            'description'       => 'required|string|max:1000',
            'store_name'        => 'nullable|string|max:255',
            'store_address'     => 'nullable|string|max:500',
            'estimated_amount'  => 'nullable|numeric|min:0',
            'pickup_address'    => 'required|string|max:500',
            'pickup_lat'        => 'nullable|numeric',
            'pickup_lng'        => 'nullable|numeric',
            'delivery_address'  => 'required|string|max:500',
            'delivery_lat'      => 'nullable|numeric',
            'delivery_lng'      => 'nullable|numeric',
            'recipient_name'    => 'nullable|string|max:255',
            'recipient_phone'   => 'nullable|string|max:20',
            'payment_method_code'=> 'nullable',
        ]);

        // Calculate delivery fee
        $fee = 10;
        if ($request->pickup_lat && $request->pickup_lng && $request->delivery_lat && $request->delivery_lng) {
            $estimate = DeliveryPricing::estimateForPoints(
                $request->pickup_lat, $request->pickup_lng,
                $request->delivery_lat, $request->delivery_lng
            );
            $fee = $estimate['delivery_fee'] ?? 10;
        }

        $total = $fee + (float) ($request->estimated_amount ?? 0);

        $pinCode = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $orderNo = 'FAV-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $favor = Favor::create([
            'order_no'           => $orderNo,
            'user_id'            => auth()->id(),
            'type'               => $request->type,
            'description'        => $request->description,
            'store_name'         => $request->store_name,
            'store_address'      => $request->store_address,
            'estimated_amount'   => $request->estimated_amount ?? 0,
            'pickup_address'     => $request->pickup_address,
            'pickup_lat'         => $request->pickup_lat,
            'pickup_lng'         => $request->pickup_lng,
            'delivery_address'   => $request->delivery_address,
            'delivery_lat'       => $request->delivery_lat,
            'delivery_lng'       => $request->delivery_lng,
            'recipient_name'     => $request->recipient_name,
            'recipient_phone'    => $request->recipient_phone,
            'delivery_fee'       => $fee,
            'total'              => $total,
            'status'             => 'searching_courier',
            'pin_code'           => $pinCode,
            'payment_method_code' => $request->payment_method_code,
            'payment_method_name' => $request->payment_method_code && $request->payment_method_code !== '0'
                ? (Gateway::where('code', $request->payment_method_code)->first()?->name ?? 'Efectivo')
                : 'Efectivo',
        ]);

        return redirect()->route('favor.detail', $favor->order_no)->withNotify([['success', '¡Favor creado! Te notificaremos cuando un repartidor lo acepte. PIN: ' . $pinCode]]);
    }

    public function favorDetail($orderNo)
    {
        $pageTitle = 'Detalle del Favor';
        $isDeliveryRoute = false;
        $favor = Favor::where('order_no', $orderNo)
            ->where('user_id', auth()->id())
            ->with('courier', 'bids.courier', 'messages')
            ->firstOrFail();

        return view('Template::delivery.favor_detail', compact('pageTitle', 'isDeliveryRoute', 'favor'));
    }

    public function rideStatus($id)
    {
        $ride = Ride::where('user_id', auth()->id())->findOrFail($id);
        $driver = $ride->driver;
        return response()->json([
            'status'     => $ride->status,
            'driver'     => $driver ? [
                'name'    => $driver->fullname,
                'phone'   => $driver->mobile,
                'dial_code' => $driver->dial_code,
                'vehicle' => $driver->vehicle?->name ?? 'Vehículo',
                'rating'  => 4.8,
                'lat'     => $driver->current_lat,
                'lng'     => $driver->current_lot,
                'image'   => $driver->imageSrc,
            ] : null,
        ]);
    }

    public function userRides()
    {
        if (!auth()->check()) {
            return response()->json(['rides' => []]);
        }
        $rides = Ride::where('user_id', auth()->id())
            ->with('service')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($r) => [
                'id'         => $r->id,
                'uid'        => $r->uid,
                'service'    => $r->service?->name ?? '',
                'pickup'     => $r->pickup_location,
                'destination'=> $r->destination,
                'distance'   => $r->distance,
                'amount'     => $r->amount,
                'status'     => $r->status,
                'created_at' => $r->created_at->diffForHumans(),
            ]);
        return response()->json(['rides' => $rides]);
    }

    public function deliveryMarketplace(Request $request)
    {
        $pageTitle = 'Delivery en Tarapoto — Pide comida, productos y más | Lizto Delivery';

        // Cargar SEO desde la base de datos (/admin/seo)
        $seo = \App\Models\Frontend::where('data_keys', 'seo.data')->first();
        $seoContents = $seo ? $seo->seo_content : null;
        $seoImage = $seo ? getImage(getFilePath('seo') . '/' . @$seo->data_values->image) : siteLogo();
        $selectedCatParam = $request->query('category');
        $selectedSubCatParam = $request->query('subcategory');
        $activeCategory = null;

        $categories = \App\Models\GeneralCategory::active()->with('subCategories')->orderBy('sort_order')->get();

        $subCategoriesQuery = \App\Models\SubCategory::active()->orderBy('sort_order');

        if ($selectedCatParam) {
            if ($selectedCatParam === 'markets') {
                $marketCatIds = $categories->whereIn('slug', ['super-mini-markets', 'farmacia'])->pluck('id')->toArray();
                if (empty($marketCatIds)) $marketCatIds = [2, 6];
                $subCategoriesQuery->whereIn('general_category_id', $marketCatIds);
            } elseif (is_numeric($selectedCatParam)) {
                $subCategoriesQuery->where('general_category_id', $selectedCatParam);
                $activeCategory = $categories->firstWhere('id', (int)$selectedCatParam);
            } else {
                $activeCategory = $categories->firstWhere('slug', $selectedCatParam);
                if ($activeCategory) {
                    $subCategoriesQuery->where('general_category_id', $activeCategory->id);
                }
            }
        }

        $subCategories = $subCategoriesQuery->get();
        $query = Store::active()->open()->withActivePackages()->with(['subCategories', 'generalCategories'])->withCount('products');

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($storeQuery) use ($search) {
                $storeQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('products', fn ($product) => $product->where('name', 'like', "%{$search}%"));
            });
        }

        if ($selectedCatParam) {
            if ($selectedCatParam === 'markets') {
                $marketCatIds = $categories->whereIn('slug', ['super-mini-markets', 'farmacia'])->pluck('id')->toArray();
                if (empty($marketCatIds)) $marketCatIds = [2, 6];
                $query->whereHas('generalCategories', fn ($category) => $category->whereIn('general_categories.id', $marketCatIds));
            } elseif (is_numeric($selectedCatParam)) {
                $query->whereHas('generalCategories', fn ($category) => $category->where('general_categories.id', $selectedCatParam));
            } else {
                if ($activeCategory) {
                    $query->whereHas('generalCategories', fn ($category) => $category->where('general_categories.id', $activeCategory->id));
                }
            }
        }

        if ($selectedSubCatParam) {
            $query->whereHas('subCategories', fn ($subCategory) => $subCategory->where('sub_categories.id', $selectedSubCatParam));
        }

        $stores = $query->orderByFeatured()->get();
        $products = \App\Models\Product::active()->with('store')->where('is_promoted', true)->orderBy('sort_order')->take(10)->get();

        // Más pedidos: top 10 productos por cantidad vendida
        $mostOrdered = \App\Models\DeliveryOrderItem::selectRaw('product_id, product_name, SUM(quantity) as total_qty, MAX(delivery_order_items.created_at) as last_ordered')
            ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
            ->where('delivery_orders.status', 'delivered')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(8)
            ->get()
            ->map(function($item) {
                $product = \App\Models\Product::with('store')->find($item->product_id);
                $item->product = $product;
                return $item;
            })->filter(fn($i) => $i->product && $i->product->status);
        $storeCount = Schema::hasTable('stores') ? Store::active()->count() : 0;
        $packages = BusinessPackage::active()->orderBy('sort_order')->get();

        // Verificar envíos gratis del usuario
        $hasFreeDelivery = false;
        $freeRemaining = 0;
        if (auth()->check()) {
            $freeDelivery = FreeDelivery::where('user_id', auth()->id())
                ->where('status', 1)->where('remaining', '>', 0)->first();
            if ($freeDelivery) { $hasFreeDelivery = true; $freeRemaining = $freeDelivery->remaining; }
        }

        // Calculate delivery fees based on user location
        $location = Session::get('delivery_location', []);
        $userLat = $location['lat'] ?? null;
        $userLng = $location['lng'] ?? null;
        foreach ($stores as $store) {
            $estimate = DeliveryPricing::estimateForStore(
                $store,
                $userLat !== null ? (float) $userLat : null,
                $userLng !== null ? (float) $userLng : null
            );
            $store->display_fee = $estimate['delivery_fee'];
            $store->delivery_fee_estimate = $estimate;
        }

        $banners = \App\Models\Banner::active()->orderBy('sort_order')->get();
        $userId = auth()->id();
        $coupons = \App\Models\Coupon::active()->get()->filter(fn($c) => $c->isValid($userId));
        $discountedProducts = \App\Models\Product::active()
            ->where(function($q) {
                $q->whereNotNull('discount_price')->where('discount_price', '>', 0);
            })
            ->with('store')
            ->orderBy('id', 'desc')
            ->take(12)
            ->get();

        return view('Template::delivery.marketplace', compact(
            'pageTitle', 'seoContents', 'seoImage', 'categories', 'subCategories', 'stores', 'products', 
            'storeCount', 'packages', 'mostOrdered', 'hasFreeDelivery', 'freeRemaining',
            'banners', 'coupons', 'discountedProducts', 'activeCategory'
        ));
    }

    public function deliveryStore(Store $store)
    {
        abort_unless($store->status && $store->is_open, 404);
        $pageTitle = $store->name;
        $seoContents = null;
        $seoImage = null;
        $store->load(['categories.products.variations', 'categories.products.addons']);
        $location = Session::get('delivery_location', []);
        $estimate = DeliveryPricing::estimateForStore(
            $store,
            isset($location['lat']) ? (float) $location['lat'] : null,
            isset($location['lng']) ? (float) $location['lng'] : null
        );
        $store->display_fee = $estimate['delivery_fee'];

        $productsJson = $store->categories->pluck('products')->flatten()->keyBy('id')->map(function($p) {
            return [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'price'       => $p->finalPrice(),
                'image'       => $p->image ? getImage(getFilePath('product') . '/' . $p->image) : null,
                'variations'  => $p->variations->map(fn($v) => ['id' => $v->id, 'name' => $v->name, 'price' => (float) $v->price])->values(),
                'addons'      => $p->addons->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price])->values(),
            ];
        })->toJson();

        return view('Template::delivery.store', compact('pageTitle', 'seoContents', 'seoImage', 'store', 'productsJson', 'estimate'));
    }

    public function validateCoupon(Request $request)
    {
        $coupon = \App\Models\Coupon::where('code', $request->code)->first();
        if (!$coupon || !$coupon->isValid(auth()->id())) {
            return response()->json(['status' => 'error', 'message' => 'Cupón inválido o ya utilizado']);
        }
        $subtotal = floatval($request->subtotal ?? 0);
        if ($subtotal < $coupon->min_order) {
            return response()->json(['status' => 'error', 'message' => 'Pedido mínimo S/ ' . number_format($coupon->min_order, 2)]);
        }
        $discount = $coupon->type === 'free_delivery' ? 0 : $coupon->calcDiscount($subtotal);
        return response()->json([
            'status' => 'success',
            'data' => ['discount' => $discount, 'type' => $coupon->type, 'description' => $coupon->description ?: $coupon->name]
        ]);
    }

    public function businessLanding()
    {
        $pageTitle = 'Registra tu Negocio — Lizto';
        $packages = BusinessPackage::active()->orderBy('price', 'asc')->get();
        return view('Template::delivery.negocios', compact('pageTitle', 'packages'));
    }

    public function taxiPage()
    {
        $pageTitle = __('Taxi - Viajes Seguros y Rápidos');
        $seoContents = null;
        $seoImage = null;
        $taxiServices = Service::active()->get();

        return view('Template::taxi', compact('pageTitle', 'seoContents', 'seoImage', 'taxiServices'));
    }

    public function pages($slug)
    {
        $page        = Page::where('tempname', activeTemplate())->where('slug', $slug)->firstOrFail();
        $pageTitle   = $page->name;
        $sections    = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
    }


    public function contact()
    {
        $pageTitle = __('Contact Us');
        $user        = auth()->user();
        $sections    = Page::where('tempname', activeTemplate())->where('slug', 'contact')->first();
        $seoContents = $sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::contact', compact('pageTitle', 'user', 'sections', 'seoContents', 'seoImage'));
    }


    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name'    => 'required',
            'email'   => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', __('Invalid captcha provided')];
            return back()->withNotify($notify);
        }

        $random = getNumber();

        $ticket           = new SupportTicket();
        $ticket->user_id  = auth()->id() ?? 0;
        $ticket->name     = $request->name;
        $ticket->email    = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;


        $ticket->ticket     = $random;
        $ticket->subject    = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status     = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title = __('A new contact message has been submitted');
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message                    = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message           = $request->message;
        $message->save();

        $notify[] = ['success', __('Ticket created successfully')];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug)
    {
        $policy      = Frontend::where('slug', $slug)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle   = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function changeLanguage($lang = null)
    {
        $language          = Language::where('code', $lang)->first();
        if (!$language) $lang = 'en';
        session()->put('lang', $lang);
        return back();
    }

    public function blog()
    {
        $pageTitle = __('Blogs');
        $blogs       = Frontend::where('data_keys', 'blog.element')->latest('id')->paginate(getPaginate(18));
        $sections    = Page::where('tempname', activeTemplate())->where('slug', 'blog')->first();
        $seoContents = $sections->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('blog', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::blog', compact('pageTitle', 'blogs', 'sections', 'seoContents', 'seoImage'));
    }


    public function blogDetails($slug)
    {
        $blog        = Frontend::where('slug', $slug)->where('data_keys', 'blog.element')->firstOrFail();
        $latestBlogs = Frontend::where('slug', '!=', $slug)->where('data_keys', 'blog.element')->take(10)->latest('id')->get();
        $pageTitle = __('Blog Details');
        $seoContents = $blog->seo_content;
        if ($seoContents) {
            $seoImage = frontendImage('blog', $seoContents->image, getFileSize('seo'), true);
        } else {
            $seoContents = (object) [
                'title'              => $blog->data_values->title,
                'social_title'       => $blog->data_values->title,
                'description'        => strLimit(strip_tags(@$blog->data_values->description), 300),
                'social_description' => strLimit(strip_tags(@$blog->data_values->description), 300),
            ];
            $seoImage = frontendImage('blog', @$blog->data_values->image);
        }
        return view('Template::blog_details', compact('blog', 'pageTitle', 'seoContents', 'seoImage', 'latestBlogs'));
    }


    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy()
    {
        $cookieContent = Frontend::where('data_keys', 'cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE, 404);
        $pageTitle = __('Cookie Policy');
        $cookie    = Frontend::where('data_keys', 'cookie.data')->first();
        return view('Template::cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth  = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text      = $imgWidth . '×' . $imgHeight;
        $fontFile  = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize  = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox    = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = __('Maintenance Mode');
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view('Template::maintenance', compact('pageTitle', 'maintenance'));
    }

    public function subscribe(Request $request)
    {

        $validator = validator()->make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ], [
            "email.unique" => 'You are already with us.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->getMessages()]);
        }

        $subscribe        = new Subscriber();
        $subscribe->email = $request->email;
        $subscribe->save();

        return response()->json(['success' => true, 'message' => __('Thanks for connecting with us.')]);
    }

    public function tokenLogin(Request $request)
    {
        $token    = $request->query('token') ?? $request->input('token');
        $redirect = $request->query('redirect', '/');
        $guard    = $request->query('guard', 'web');

        if (!$token) {
            return redirect('/')->with('error', 'Token no proporcionado');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            return redirect('/')->with('error', 'Token inválido o expirado');
        }

        $user = $accessToken->tokenable;

        if ($guard === 'driver') {
            Auth::guard('driver')->login($user);
        } else {
            Auth::guard('web')->login($user);
        }

        return redirect($redirect);
    }

    public function deliveryFeeEstimate(Request $request)
    {
        $store = Store::where('status', 1)->find($request->store_id);
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'Tienda no encontrada'], 404);
        }

        $location = Session::get('delivery_location', []);
        [$requestLat, $requestLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        $lat = $requestLat ?? $location['lat'] ?? null;
        $lng = $requestLng ?? $location['lng'] ?? null;

        $estimate = DeliveryPricing::estimateForStore($store, $lat ? (float) $lat : null, $lng ? (float) $lng : null);

        return response()->json([
            'status'       => 'success',
            'delivery_fee' => $estimate['delivery_fee'],
            'distance_km'  => $estimate['distance_km'],
            'base_fare'    => $estimate['base_fare'],
            'distance_fee' => $estimate['distance_fee'],
            'time_fee'     => $estimate['time_fee'],
            'time_min'     => $estimate['time_min'],
            'surge'        => $estimate['surge'],
            'in_coverage'  => $estimate['in_coverage'],
        ]);
    }

    public function feeEstimate(Request $request)
    {
        $pickupLat = (float) $request->pickup_lat;
        $pickupLng = (float) $request->pickup_lng;
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);

        if (!$pickupLat || !$pickupLng || !DeliveryPricing::hasCoordinates($deliveryLat, $deliveryLng)) {
            return response()->json(['delivery_fee' => 10, 'distance_km' => 0], 200);
        }

        $estimate = DeliveryPricing::estimateForPoints($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
        return response()->json($estimate);
    }

    /**
     * Fee estimate by store ID + delivery coordinates.
     * Used by the store detail page and marketplace re-fetch.
     */
    public function storeFeeEstimate(Request $request)
    {
        $store = Store::active()->find($request->store_id);
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        if (!$store || !DeliveryPricing::hasCoordinates($deliveryLat, $deliveryLng)) {
            return response()->json(['delivery_fee' => 0, 'distance_km' => 0]);
        }

        $estimate = DeliveryPricing::estimateForStore(
            $store,
            $deliveryLat,
            $deliveryLng
        );

        return response()->json($estimate);
    }

    // ===== User Location (Session-based) =====

    public function locationGet()
    {
        $location = Session::get('delivery_location', []);
        return response()->json([
            'status' => 'success',
            'lat'    => $location['lat'] ?? null,
            'lng'    => $location['lng'] ?? null,
            'label'  => $location['label'] ?? null,
        ]);
    }

    public function locationSave(Request $request)
    {
        $request->validate([
            'lat'   => 'nullable|numeric',
            'lng'   => 'nullable|numeric',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'label' => 'nullable|string|max:255',
        ]);

        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);

        Session::put('delivery_location', [
            'lat'   => $lat,
            'lng'   => $lng,
            'label' => $request->label,
        ]);

        return response()->json(['status' => 'success']);
    }

    // ===== Checkout =====

    public function checkout(Request $request)
    {
        $storeId = $request->query('store');
        $store = Store::where('status', 1)->findOrFail($storeId);
        $cart = Session::get("cart_{$storeId}", []);

        // Require user registration / authentication for checkout
        if (!auth()->check()) {
            $token = $request->query('token');
            if ($token) {
                return redirect('/auth/token-login?token=' . urlencode($token) . '&redirect=' . urlencode($request->fullUrl()));
            }
            $notify[] = ['warning', 'Debes registrarte o iniciar sesión para continuar con tu pedido.'];
            return redirect()->route('delivery.store', ['store' => $store->id, 'require_login' => 1])->withNotify($notify);
        }

        if (empty($cart)) {
            return redirect()->route('delivery.store', $store)->with('error', 'Tu carrito está vacío');
        }

        Session::put('checkout_store_id', $storeId);

        $location = Session::get('delivery_location', []);
        $estimate = DeliveryPricing::estimateForStore(
            $store,
            $location['lat'] ?? null,
            $location['lng'] ?? null
        );

        $gateways = \App\Models\Gateway::active()->with('singleCurrency')->get()
            ->map(fn($g) => [
                'code'        => $g->code,
                'name'        => $g->name,
                'image'       => $g->singleCurrency?->image,
                'currency'    => $g->singleCurrency?->currency ?? 'PEN',
                'description' => $g->description,
            ]);

        $userInfo = [];
        if (auth()->check()) {
            $userInfo = [
                'firstname' => auth()->user()->firstname ?? '',
                'mobile'    => auth()->user()->mobile ?? '',
            ];
        }

        $deliveryFee = $estimate['delivery_fee'];
        $pageTitle = 'Checkout — ' . $store->name;

        return view('Template::delivery.checkout', compact(
            'pageTitle', 'store', 'cart', 'deliveryFee', 'estimate',
            'gateways', 'userInfo', 'location'
        ));
    }

    public function checkoutSubmit(Request $request)
    {
        $storeId = $request->store_id;
        $store = Store::where('status', 1)->findOrFail($storeId);
        $cart = Session::get("cart_{$storeId}", []);

        if (empty($cart)) {
            return back()->with('error', 'Tu carrito está vacío');
        }

        $request->validate([
            'contact_name'       => 'required|string|max:255',
            'contact_phone'      => 'required|string|max:20',
            'delivery_address'   => 'required|string|max:500',
            'delivery_lat'       => 'nullable|numeric',
            'delivery_lng'       => 'nullable|numeric',
            'notes'              => 'nullable|string|max:500',
            'payment_method_code'=> 'nullable',
            'tip_amount'         => 'nullable|numeric|min:0',
        ]);

        $location = Session::get('delivery_location', []);
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        $deliveryLat = $deliveryLat ?? ($location['lat'] ?? null);
        $deliveryLng = $deliveryLng ?? ($location['lng'] ?? null);

        $estimate = DeliveryPricing::estimateForStore(
            $store,
            $deliveryLat,
            $deliveryLng
        );
        $deliveryFee = $estimate['delivery_fee'];

        $tip = (float) ($request->tip_amount ?? 0);
        $subtotal = collect($cart)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 0));
        $discount = 0;
        $coupon = null;

        // Cupón
        if ($request->coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $request->coupon_code)->first();
            if ($coupon && $coupon->isValid(auth()->id()) && $subtotal >= $coupon->min_order) {
                $discount = $coupon->type === 'free_delivery' ? $deliveryFee : $coupon->calcDiscount($subtotal);
                \App\Models\CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => auth()->id()]);
                $coupon->increment('usage_count');
            }
        }

        // Envío gratis asignado
        if (!$coupon || $coupon->type !== 'free_delivery') {
            $freeDelivery = \App\Models\FreeDelivery::where('user_id', auth()->id())->where('status', 1)->where('remaining', '>', 0)->first();
            if ($freeDelivery) { $discount = max($discount, $deliveryFee); $freeDelivery->decrement('remaining'); }
        }

        $total = $subtotal + $deliveryFee + $tip - $discount;

        $paymentCode = $request->payment_method_code;
        $paymentName = 'Efectivo';
        $isMercadoPago = false;
        $gw = null;
        if ($paymentCode && $paymentCode !== '0') {
            $gw = \App\Models\Gateway::where('code', $paymentCode)->active()->first();
            if ($gw) {
                $paymentName = $gw->name;
                if ($gw->alias === 'MercadoPago') {
                    $isMercadoPago = true;
                }
            }
        }

        $order = \App\Models\DeliveryOrder::create([
            'order_no'            => 'ORD-' . strtoupper(getTrx(8)),
            'user_id'             => auth()->id(),
            'store_id'            => $store->id,
            'subtotal'            => $subtotal,
            'delivery_fee'        => $deliveryFee,
            'discount'            => $discount,
            'tip'                 => $tip,
            'total'               => $total,
            'status'              => $isMercadoPago ? 'pending_payment' : 'pending',
            'delivery_address'    => $request->delivery_address,
            'delivery_lat'        => $deliveryLat,
            'delivery_lng'        => $deliveryLng,
            'contact_phone'       => $request->contact_phone,
            'contact_name'        => $request->contact_name,
            'notes'               => $request->notes,
            'payment_method_code' => $paymentCode,
            'payment_method_name' => $paymentName,
        ]);

        // Save user's delivery location for emergency/audit purposes
        if (!empty($deliveryLat) && !empty($deliveryLng)) {
            auth()->user()->update([
                'latitude'  => $deliveryLat,
                'longitude' => $deliveryLng,
            ]);
        }

        // Create order items
        foreach ($cart as $item) {
            \App\Models\DeliveryOrderItem::create([
                'delivery_order_id' => $order->id,
                'product_id'        => $item['product_id'],
                'product_name'      => $item['name'] ?? '',
                'quantity'          => $item['quantity'] ?? 1,
                'unit_price'        => $item['price'] ?? 0,
                'total_price'       => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
            ]);
        }

        // Clear cart
        Session::forget("cart_{$storeId}");

        if ($isMercadoPago && $gw) {
            $param = json_decode($gw->gateway_parameters);
            $accessToken = $param->access_token->value ?? ($param->access_token ?? null);

            // Calculate total including MercadoPago commission
            $currency    = $gw->singleCurrency;
            $pct         = max(0, (float) ($currency->percent_charge ?? 0));
            $fix         = max(0, (float) ($currency->fixed_charge ?? 0));
            $baseTotal   = (float) $order->total;
            $totalWithFee = $pct >= 100 ? $baseTotal + $fix : round(($baseTotal + $fix) / (1 - ($pct / 100)), 2);

            if ($accessToken) {
                $preferenceData = [
                    'items' => [
                        [
                            'id' => $order->order_no,
                            'title' => 'Pedido ' . $order->order_no,
                            'description' => 'Compra en ' . $store->name,
                            'quantity' => 1,
                            'currency_id' => 'PEN',
                            'unit_price' => $totalWithFee,
                        ]
                    ],
                    'marketplace_fee' => round($totalWithFee - $baseTotal, 2),
                    'payer' => [
                        'name' => $order->contact_name,
                        'phone' => [
                            'number' => $order->contact_phone
                        ],
                        'email' => auth()->user()->email,
                    ],
                    'back_urls' => [
                        'success' => route('user.order.detail', $order->id),
                        'pending' => route('user.order.detail', $order->id),
                        'failure' => route('user.order.detail', $order->id),
                    ],
                    'notification_url' => url('api/ipn/wallet-mercadopago'),
                    'auto_return' => 'approved',
                    'external_reference' => $order->order_no,
                ];

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL             => "https://api.mercadopago.com/checkout/preferences",
                    CURLOPT_CUSTOMREQUEST   => "POST",
                    CURLOPT_POSTFIELDS      => json_encode($preferenceData),
                    CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_TIMEOUT         => 30,
                    CURLOPT_HTTPHEADER      => [
                        "Content-Type: application/json",
                        "Authorization: Bearer " . $accessToken
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($curl);
                $result = json_decode($response, true);
                $err = curl_error($curl);
                curl_close($curl);

                if (!$err && isset($result['init_point'])) {
                    return redirect($result['init_point']);
                }
            }

            // Revert order status if MercadoPago preference fails
            $order->update(['status' => 'pending']);
            event(new \App\Events\NewDeliveryOrderPlaced($order, 'delivery'));
            $notify[] = ['error', 'Hubo un problema al conectar con MercadoPago. Tu pedido se ha registrado como pendiente de pago en efectivo/espera.'];
            return redirect()->route('home')->withNotify($notify);
        }

        event(new \App\Events\NewDeliveryOrderPlaced($order, 'delivery'));

        $notify[] = ['success', '¡Pedido creado! Te notificaremos cuando la tienda lo confirme.'];
        return redirect()->route('home')->withNotify($notify);
    }

    // ===== Cart Management (Session-based) =====

    public function cartGet($storeId)
    {
        $cart = Session::get("cart_{$storeId}", []);
        $store = Store::find($storeId);
        $location = Session::get('delivery_location', []);
        $estimate = $store ? DeliveryPricing::estimateForStore(
            $store,
            isset($location['lat']) ? (float) $location['lat'] : null,
            isset($location['lng']) ? (float) $location['lng'] : null
        ) : null;
        $deliveryFee = $estimate['delivery_fee'] ?? 0;
        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']);
        $total = $subtotal + $deliveryFee;

        return response()->json([
            'status'       => 'success',
            'cart'         => array_values($cart),
            'item_count'   => collect($cart)->sum('quantity'),
            'subtotal'     => round($subtotal, 2),
            'delivery_fee' => round($deliveryFee, 2),
            'total'        => round($total, 2),
        ]);
    }

    public function cartAdd(Request $request, $storeId)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'name'       => 'required|string',
            'price'      => 'required|numeric',
            'quantity'   => 'integer|min:1',
        ]);

        $cart = Session::get("cart_{$storeId}", []);
        $productId = $request->product_id;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $request->quantity ?? 1;
        } else {
            $cart[$productId] = [
                'product_id' => $request->product_id,
                'name'       => $request->name,
                'price'      => (float) $request->price,
                'quantity'   => $request->quantity ?? 1,
            ];
        }

        Session::put("cart_{$storeId}", $cart);

        $count = collect($cart)->sum('quantity');
        return response()->json(['status' => 'success', 'item_count' => $count, 'total_items' => $this->totalCartItems()]);
    }

    public function cartRemove(Request $request, $storeId)
    {
        $request->validate(['product_id' => 'required|integer']);

        $cart = Session::get("cart_{$storeId}", []);
        $productId = $request->product_id;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] -= $request->quantity ?? 1;
            if ($cart[$productId]['quantity'] <= 0) {
                unset($cart[$productId]);
            }
        }

        Session::put("cart_{$storeId}", $cart);

        $count = collect($cart)->sum('quantity');
        return response()->json(['status' => 'success', 'item_count' => $count, 'total_items' => $this->totalCartItems()]);
    }

    public function cartClear($storeId)
    {
        Session::forget("cart_{$storeId}");
        return response()->json(['status' => 'success', 'total_items' => $this->totalCartItems()]);
    }

    public function cartSummary()
    {
        $activeCarts = [];
        foreach (Session::all() as $key => $items) {
            if (str_starts_with($key, 'cart_') && is_array($items) && !empty($items)) {
                $storeId = str_replace('cart_', '', $key);
                $store = Store::find($storeId);
                if ($store) {
                    $subtotal = collect($items)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));
                    $location = Session::get('delivery_location', []);
                    $estimate = DeliveryPricing::estimateForStore(
                        $store,
                        isset($location['lat']) ? (float) $location['lat'] : null,
                        isset($location['lng']) ? (float) $location['lng'] : null
                    );
                    $deliveryFee = $estimate['delivery_fee'] ?? 0;
                    $activeCarts[] = [
                        'store_id'     => $store->id,
                        'store_name'   => $store->name,
                        'store_slug'   => $store->slug,
                        'store_image'  => $store->logo ? getImage(getFilePath('store') . '/' . $store->logo) : null,
                        'items'        => array_values($items),
                        'item_count'   => collect($items)->sum('quantity'),
                        'subtotal'     => round($subtotal, 2),
                        'delivery_fee' => round($deliveryFee, 2),
                        'total'        => round($subtotal + $deliveryFee, 2),
                        'checkout_url' => route('delivery.checkout', ['store' => $store->id]),
                    ];
                }
            }
        }

        $totalItems = collect($activeCarts)->sum('item_count');

        return response()->json([
            'status'      => 'success',
            'total_items' => $totalItems,
            'carts'       => $activeCarts,
        ]);
    }

    public function cartCount()
    {
        return response()->json(['status' => 'success', 'total_items' => $this->totalCartItems()]);
    }

    private function totalCartItems()
    {
        $total = 0;
        foreach (Session::all() as $key => $value) {
            if (str_starts_with($key, 'cart_') && is_array($value)) {
                $total += collect($value)->sum('quantity');
            }
        }
        return $total;
    }
}
