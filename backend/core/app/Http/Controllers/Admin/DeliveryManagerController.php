<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCommission;
use App\Models\DeliveryOrder;
use App\Models\DeliveryRefund;
use App\Models\Favor;
use App\Models\GeneralCategory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductAddon;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StoreSchedule;
use App\Models\SubCategory;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Driver;
use App\Models\BusinessPackage;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\FreeDelivery;
use App\Models\StorePackage;
use App\Models\StorePackagePayment;
use App\Models\Transaction;
use App\Constants\Status;
use App\Services\FcmService;
use App\Events\FavorStatusUpdated;
use App\Services\AdminDeliveryRequestDispatchService;
use App\Services\DeliveryFinancialLedger;
use App\Models\CourierEarning;
use App\Support\DeliveryPricing;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DeliveryManagerController extends Controller
{
    // ── Dashboard ──

    public function dashboard()
    {
        $pageTitle = 'Dashboard Delivery';

        $stats = [
            'total_orders'      => DeliveryOrder::count(),
            'today_orders'      => DeliveryOrder::whereDate('created_at', today())->count(),
            'pending_orders'    => DeliveryOrder::where('status', 'pending')->count(),
            'delivered_orders'  => DeliveryOrder::where('status', 'delivered')->count(),
            'canceled_orders'   => DeliveryOrder::where('status', 'cancelled')->count(),
            'total_favors'      => Favor::count(),
            'today_favors'      => Favor::whereDate('created_at', today())->count(),
            'pending_favors'    => Favor::where('status', 'searching_courier')->count(),
            'delivered_favors'  => Favor::where('status', 'delivered')->count(),
            'total_stores'      => Store::count(),
            'active_stores'     => Store::where('status', 1)->count(),
            'pending_refunds'   => DeliveryRefund::where('status', 'pending')->count(),
            'total_couriers'    => Driver::count(),
            'total_revenue'     => DeliveryOrder::where('status', 'delivered')->sum('total')
                                 + Favor::where('status', 'delivered')->sum('total'),
            'total_commission'  => DeliveryOrder::sum('commission_amount') + Favor::sum('commission_amount'),
            'today_revenue'     => DeliveryOrder::where('status', 'delivered')->whereDate('created_at', today())->sum('total')
                                 + Favor::where('status', 'delivered')->whereDate('created_at', today())->sum('total'),
        ];

        $commission = DeliveryCommission::first();

        return view('admin.delivery.dashboard', compact('pageTitle', 'stats', 'commission'));
    }

    // ── Orders ──

    public function orders(Request $request)
    {
        $pageTitle = 'Pedidos Delivery';
        $orders = DeliveryOrder::with('user', 'store', 'driver')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('order_no', 'like', "%{$request->search}%"))
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('admin.delivery.orders', compact('pageTitle', 'orders'));
    }

    public function orderDetail($id)
    {
        $pageTitle = 'Detalle de Pedido';
        $order = DeliveryOrder::with('user', 'store', 'driver', 'items.variation', 'items.addons', 'items.product')->findOrFail($id);
        $drivers = Driver::where('status', 1)->get();
        return view('admin.delivery.order_detail', compact('pageTitle', 'order', 'drivers'));
    }

    public function orderStatus(Request $request, $id)
    {
        $order = DeliveryOrder::findOrFail($id);
        $order->update(['status' => $request->status]);

        if ($request->status === 'delivered') {
            $order->update(['delivered_at' => now()]);
            $this->processCommission($order);
        }

        if ($request->driver_id) {
            $order->update(['driver_id' => $request->driver_id, 'status' => 'confirmed']);
        }

        if ($order->store && $order->store->seller) {
            $statusLabels = [
                'confirmed' => 'Pedido Confirmado',
                'preparing' => 'Preparando Pedido',
                'ready' => 'Pedido Listo',
                'on_way' => 'Repartidor en Camino',
                'delivered' => 'Pedido Entregado',
                'cancelled' => 'Pedido Cancelado',
            ];
            $label = $statusLabels[$order->status] ?? 'Pedido Actualizado';
            FcmService::sendToSeller($order->store->seller, $label, 'El administrador actualizó el estado del pedido #' . $order->order_no . ' a: ' . $label, ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'status_updated']);
        }

        \Illuminate\Support\Facades\Broadcast::driver('reverb')->broadcast(
            ['private-delivery-order.' . $order->user_id, 'private-tracking.' . $order->id],
            'delivery_order_status_updated',
            ['order_id' => $order->id, 'status' => $order->status, 'message' => 'Pedido actualizado', 'order_no' => $order->order_no]
        );

        $notify[] = ['success', 'Estado actualizado'];
        return back()->withNotify($notify);
    }

    public function assignDriver(Request $request, $id)
    {
        $order = DeliveryOrder::findOrFail($id);
        $order->update(['driver_id' => $request->driver_id, 'status' => 'confirmed', 'driver_assigned_at' => now()]);

        if ($order->store && $order->store->seller) {
            $driverName = $order->driver ? ($order->driver->firstname . ' ' . $order->driver->lastname) : 'Repartidor';
            FcmService::sendToSeller($order->store->seller, 'Repartidor asignado', 'El administrador asignó al repartidor ' . $driverName . ' para el pedido #' . $order->order_no, ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'driver_assigned']);
        }

        $notify[] = ['success', 'Repartidor asignado'];
        return back()->withNotify($notify);
    }

    // ── Favors ──

    public function favors(Request $request)
    {
        $pageTitle = 'Favores';
        $favors = Favor::with('user', 'courier')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('order_no', 'like', "%{$request->search}%"))
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('admin.delivery.favors', compact('pageTitle', 'favors'));
    }

    public function favorDetail($id)
    {
        $pageTitle = 'Detalle de Favor';
        $favor = Favor::with('user', 'courier', 'bids.courier', 'messages')->findOrFail($id);
        $drivers = Driver::where('status', 1)->orderBy('firstname')->orderBy('lastname')->get();

        $pusherConfig = [
            'key'    => env('PUSHER_APP_KEY', env('REVERB_APP_KEY', '')),
            'host'   => trim(env('REVERB_HOST', env('PUSHER_HOST', 'localhost')), '"'),
            'port'   => env('REVERB_PORT', env('PUSHER_PORT', 8080)),
            'scheme' => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')),
        ];

        return view('admin.delivery.favor_detail', compact('pageTitle', 'favor', 'drivers', 'pusherConfig'));
    }

    public function createFavorTrackingShare($id)
    {
        $favor = Favor::findOrFail($id);
        if (!$favor->tracking_share_token) {
            $favor->update(['tracking_share_token' => Str::random(48)]);
        }

        return response()->json([
            'url' => route('tracking.favor.show', $favor->fresh()->tracking_share_token),
        ]);
    }

    public function broadcastingAuth(Request $request)
    {
        $socketId = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        if (!$socketId || !$channelName) {
            return response()->json(['message' => 'Missing parameters'], 422);
        }

        if (!auth('admin')->check()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Admins can track any favor / delivery order
        if (preg_match('/^private-(favor|tracking|job)\.(\d+)$/', $channelName)) {
            $reverbSecret = config('reverb.apps.apps.0.secret');
            $reverbKey    = config('reverb.apps.apps.0.key');
            $hash         = hash_hmac('sha256', $socketId . ':' . $channelName, $reverbSecret);
            return response()->json(['auth' => $reverbKey . ':' . $hash]);
        }

        return response()->json(['message' => 'Forbidden - Channel mismatch'], 403);
    }

    public function favorStatus(Request $request, $id)
    {
        $favor = Favor::findOrFail($id);
        $favor->update(['status' => $request->status]);

        if ($request->status === 'delivered') {
            $favor->update(['delivered_at' => now()]);
            $this->processFavorCommission($favor);
        }

        $notify[] = ['success', 'Estado actualizado'];
        return back()->withNotify($notify);
    }

    public function reassignFavorCourier(Request $request, $id)
    {
        $request->validate([
            'courier_id' => ['required', 'integer', 'exists:drivers,id'],
        ]);

        $courier = Driver::where('status', 1)->findOrFail($request->integer('courier_id'));
        [$favor, $previousCourier] = DB::transaction(function () use ($id, $courier) {
            $favor = Favor::lockForUpdate()->findOrFail($id);
            if (in_array($favor->status, ['delivered', 'cancelled'], true)) {
                abort(422, 'No se puede cambiar el repartidor de un favor finalizado.');
            }
            if ((int) $favor->courier_id === $courier->id) {
                abort(422, 'Este repartidor ya está asignado al favor.');
            }

            $previousCourier = $favor->courier_id ? Driver::find($favor->courier_id) : null;

            // Pending commission is stored on the Favor itself. Any earning record
            // created before reassignment must point to the new courier as well.
            if ($previousCourier) {
                CourierEarning::where('courier_id', $previousCourier->id)
                    ->where('job_type', Favor::class)
                    ->where('job_id', $favor->id)
                    ->update(['courier_id' => $courier->id]);
            }

            $favor->update([
                'courier_id'          => $courier->id,
                'status'              => 'accepted',
                'courier_assigned_at' => now(),
            ]);

            return [$favor, $previousCourier];
        });

        $favor->refresh();
        if ($previousCourier) {
            FcmService::sendToDriver(
                $previousCourier,
                'Favor reasignado',
                'El favor #' . $favor->order_no . ' fue reasignado a otro repartidor.',
                ['favor_id' => (string) $favor->id, 'order_no' => $favor->order_no, 'type' => 'favor_reassigned']
            );
        }
        FcmService::sendToDriver(
            $courier,
            'Nuevo favor asignado',
            'Te asignaron el favor #' . $favor->order_no . '. Destino: ' . ($favor->delivery_address ?: 'por confirmar'),
            ['favor_id' => (string) $favor->id, 'order_no' => $favor->order_no, 'type' => 'favor_assigned']
        );
        event(new FavorStatusUpdated($favor, 'courier_reassigned'));

        return back()->withNotify([['success', 'Repartidor cambiado y notificado.']]);
    }

    public function updateFavorDestination(Request $request, $id)
    {
        $data = $request->validate([
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_lat'     => ['required', 'numeric', 'between:-90,90'],
            'delivery_lng'     => ['required', 'numeric', 'between:-180,180'],
        ]);

        $favor = Favor::findOrFail($id);
        if (in_array($favor->status, ['delivered', 'cancelled'], true)) {
            return back()->withNotify([['error', 'No se puede cambiar el destino de un favor finalizado.']]);
        }

        $estimate = DeliveryPricing::estimateForFavor(
            $favor->pickup_lat,
            $favor->pickup_lng,
            (float) $data['delivery_lat'],
            (float) $data['delivery_lng']
        );
        if (!$estimate['in_coverage']) {
            return back()->withNotify([['error', 'El nuevo destino está fuera de la zona de cobertura.']]);
        }

        $favor->update([
            'delivery_address' => $data['delivery_address'],
            'delivery_lat'     => $data['delivery_lat'],
            'delivery_lng'     => $data['delivery_lng'],
            'delivery_fee'     => $estimate['delivery_fee'],
            'total'            => $estimate['delivery_fee'],
        ]);

        $favor->refresh();
        if ($favor->courier) {
            FcmService::sendToDriver(
                $favor->courier,
                'Destino actualizado',
                'El destino del favor #' . $favor->order_no . ' fue actualizado.',
                ['favor_id' => (string) $favor->id, 'order_no' => $favor->order_no, 'type' => 'favor_destination_updated']
            );
        }
        event(new FavorStatusUpdated($favor, 'destination_updated'));

        return back()->withNotify([['success', 'Destino actualizado y tarifa recalculada: S/ ' . number_format($estimate['delivery_fee'], 2)]]);
    }

    // ── Stores ──

    public function stores()
    {
        $pageTitle = 'Gestionar Tiendas';
        $stores = Store::with('seller', 'subCategories.generalCategory')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.delivery.stores', compact('pageTitle', 'stores'));
    }

    public function storeDetail($id)
    {
        $pageTitle = 'Detalle de Tienda';
        $store = Store::with('seller', 'subCategories.generalCategory', 'categories.products')->findOrFail($id);
        return view('admin.delivery.store_detail', compact('pageTitle', 'store'));
    }

    // ── Categories ──

    public function categories()
    {
        $pageTitle = 'Categorías';
        $categories = GeneralCategory::withCount('subCategories')->orderBy('sort_order')->get();
        return view('admin.delivery.categories', compact('pageTitle', 'categories'));
    }

    public function subCategories($categoryId)
    {
        $pageTitle = 'Subcategorías';
        $category = GeneralCategory::findOrFail($categoryId);
        $subs = SubCategory::where('general_category_id', $categoryId)->orderBy('sort_order')->get();
        return view('admin.delivery.sub_categories', compact('pageTitle', 'category', 'subs'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'nullable|unique:general_categories',
            'status' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $cat = new GeneralCategory();
        $cat->name = $request->name;
        $cat->slug = $request->slug ?? str($request->name)->slug();
        $cat->status = $request->has('status') ? 1 : 0;
        
        if ($request->hasFile('image')) {
            $cat->image = fileUploader($request->file('image'), 'assets/images/general_category');
        }
        $cat->save();

        return back()->withNotify([['success', 'Categoría creada']]);
    }

    public function categoryUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'nullable|unique:general_categories,slug,' . $id,
            'status' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $cat = GeneralCategory::findOrFail($id);
        $cat->name = $request->name;
        $cat->slug = $request->slug ?? str($request->name)->slug();
        $cat->status = $request->has('status') ? 1 : 0;
        
        if ($request->hasFile('image')) {
            $old = $cat->image;
            $cat->image = fileUploader($request->file('image'), 'assets/images/general_category', null, $old);
        }
        $cat->save();

        return back()->withNotify([['success', 'Categoría actualizada']]);
    }

    public function categoryDelete($id)
    {
        $cat = GeneralCategory::findOrFail($id);
        $cat->delete();
        return back()->withNotify([['success', 'Categoría eliminada']]);
    }

    public function subCategoryStore(Request $request, $categoryId)
    {
        $request->validate([
            'name' => 'required',
            'sort_order' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $sub = new SubCategory();
        $sub->general_category_id = $categoryId;
        $sub->name = $request->name;
        $sub->sort_order = $request->sort_order ?? 0;
        $sub->status = 1;

        if ($request->hasFile('image')) {
            $sub->image = fileUploader($request->file('image'), 'assets/images/sub_category');
        }
        $sub->save();

        return back()->withNotify([['success', 'Subcategoría creada']]);
    }

    public function subCategoryUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        $sub = SubCategory::findOrFail($id);
        $sub->name = $request->name;
        $sub->sort_order = $request->sort_order ?? 0;
        $sub->status = $request->has('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            $old = $sub->image;
            $sub->image = fileUploader($request->file('image'), 'assets/images/sub_category', null, $old);
        }
        $sub->save();

        return back()->withNotify([['success', 'Subcategoría actualizada']]);
    }

    public function subCategoryDelete($id)
    {
        $sub = SubCategory::findOrFail($id);
        $sub->delete();
        return back()->withNotify([['success', 'Subcategoría eliminada']]);
    }

    // ── Stores CRUD ──

    public function storeCreate()
    {
        $pageTitle = 'Crear Tienda';
        $store = null;
        $sellers = Seller::where('status',1)->get();
        $generalCategories = GeneralCategory::with('allSubCategories')->where('status',1)->orderBy('sort_order')->get();
        $subCategories = SubCategory::with('generalCategory')->where('status',1)->get();
        return view('admin.delivery.store_form', compact('pageTitle','store','sellers','generalCategories','subCategories'));
    }

    public function storeEdit($id)
    {
        $pageTitle = 'Editar Tienda';
        $store = Store::with('schedules', 'generalCategories', 'subCategories')->findOrFail($id);
        $sellers = Seller::where('status',1)->get();
        $generalCategories = GeneralCategory::with('allSubCategories')->where('status',1)->orderBy('sort_order')->get();
        $subCategories = SubCategory::with('generalCategory')->where('status',1)->get();
        return view('admin.delivery.store_form', compact('pageTitle','store','sellers','generalCategories','subCategories'));
    }

    public function storeSave(Request $request, $id = null)
    {
        $data = $request->validate([
            'seller_option' => 'required|in:existing,new',
            'seller_id'=>'required_if:seller_option,existing|nullable|exists:sellers,id',
            'seller_name'=>'exclude_if:seller_option,existing|required|string|max:40',
            'seller_email'=>'exclude_if:seller_option,existing|required|email|unique:sellers,email',
            'seller_password'=>'exclude_if:seller_option,existing|required|string|min:6',
            'general_category_ids'=>'nullable|array',
            'general_category_ids.*'=>'exists:general_categories,id',
            'sub_category_ids'=>'nullable|array',
            'sub_category_ids.*'=>'exists:sub_categories,id',
            'name'=>'required|string','description'=>'nullable','address'=>'nullable',
            'delivery_fee'=>'nullable|numeric','min_order_amount'=>'nullable|numeric',
            'opening_time'=>'nullable','closing_time'=>'nullable','preparation_time'=>'nullable|integer',
            'latitude'=>'nullable|numeric','longitude'=>'nullable|numeric',
              'is_open'=>'nullable','status'=>'nullable','store_type'=>'nullable|in:restaurant,supermarket,pharmacy,liquor_store,pet_shop',
              'yape_qr_string'=>'nullable|string|max:4096','plin_qr_string'=>'nullable|string|max:4096',
        ]);

        if ($request->seller_option === 'new') {
            $seller = new Seller();
            $seller->name = $request->seller_name;
            $seller->email = $request->seller_email;
            $seller->password = bcrypt($request->seller_password);
            $seller->status = 1;
            $seller->save();
            $sellerId = $seller->id;
        } else {
            $sellerId = $request->seller_id;
        }

        $storeData = [
            'seller_id' => $sellerId,
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'delivery_fee' => 0,
            'min_order_amount' => $request->min_order_amount,
            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'preparation_time' => $request->preparation_time,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_open' => $request->has('is_open') ? 1 : 0,
            'status' => $request->has('status') ? 1 : 0,
              'store_type' => $request->store_type ?? 'restaurant',
              'yape_qr_string' => $request->filled('yape_qr_string') ? trim($request->yape_qr_string) : null,
              'plin_qr_string' => $request->filled('plin_qr_string') ? trim($request->plin_qr_string) : null,
        ];

        if ($id) {
            $store = Store::findOrFail($id);
            if ($request->hasFile('image')) {
                $old = $store->image;
                $storeData['image'] = fileUploader($request->file('image'), 'assets/images/store', null, $old);
            }
            if ($request->hasFile('cover_image')) {
                $old = $store->cover_image;
                $storeData['cover_image'] = fileUploader($request->file('cover_image'), 'assets/images/store_cover', null, $old);
            }
            $store->update($storeData);
        } else {
            if ($request->hasFile('image')) {
                $storeData['image'] = fileUploader($request->file('image'), 'assets/images/store');
            }
            if ($request->hasFile('cover_image')) {
                $storeData['cover_image'] = fileUploader($request->file('cover_image'), 'assets/images/store_cover');
            }
            $store = Store::create($storeData);
        }

        // Save schedules
        if ($request->has('schedules')) {
            $store->schedules()->delete();
            foreach ($request->schedules as $day => $slots) {
                foreach ($slots as $slot) {
                    if (!empty($slot['open']) && !empty($slot['close'])) {
                        StoreSchedule::create([
                            'store_id'   => $store->id,
                            'day'        => $day,
                            'open_time'  => $slot['open'],
                            'close_time' => $slot['close'],
                        ]);
                    }
                }
            }
        }

        // Sync categories
        if ($request->has('general_category_ids')) {
            $store->generalCategories()->sync($request->general_category_ids);
        }
        if ($request->has('sub_category_ids')) {
            $store->subCategories()->sync($request->sub_category_ids);
        }

        // Assign business package
        if ($request->filled('business_package_id')) {
            $pkg = BusinessPackage::find($request->business_package_id);
            if ($pkg) {
                // Deactivate current active packages
                $store->storePackages()->where('status', 'active')->update(['status' => 'expired']);
                // Create new subscription
                StorePackage::create([
                    'store_id'   => $store->id,
                    'seller_id'  => $sellerId,
                    'package_id' => $pkg->id,
                    'status'     => 'active',
                    'amount_paid'=> $pkg->price,
                    'starts_at'  => now(),
                    'expires_at' => now()->addDays($pkg->duration_days),
                ]);
            }
        }

        return redirect()->route('admin.delivery.stores')->withNotify([['success','Tienda guardada exitosamente']]);
    }

    /** Register and activate a subscription renewal paid directly to the admin. */
    public function subscriptionRenew(Request $request, $id)
    {
        $store = Store::with('seller')->findOrFail($id);
        $data = $request->validate([
            'package_id' => 'required|exists:business_packages,id',
            'payment_method' => 'required|in:cash,yape,transfer,plin,pos',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
        $package = BusinessPackage::findOrFail($data['package_id']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($store, $package, $data) {
            $store->storePackages()->where('status', 'active')->update(['status' => 'expired']);
            $subscription = StorePackage::create([
                'store_id' => $store->id,
                'seller_id' => $store->seller_id,
                'package_id' => $package->id,
                'status' => 'active',
                'amount_paid' => $package->price,
                'starts_at' => now(),
                'expires_at' => now()->addDays($package->duration_days),
            ]);

            StorePackagePayment::create([
                'store_id' => $store->id,
                'seller_id' => $store->seller_id,
                'package_id' => $package->id,
                'store_package_id' => $subscription->id,
                'trx' => 'ADM-' . strtoupper(Str::random(16)),
                'gateway_alias' => strtoupper($data['payment_method']),
                'gateway_currency' => 'PEN',
                'package_amount' => $package->price,
                'gateway_fee' => 0,
                'total_amount' => $package->price,
                'payment_id' => $data['payment_reference'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
                'payload' => ['source' => 'admin_store_renewal', 'payment_method' => $data['payment_method'], 'notes' => $data['notes'] ?? null],
            ]);
        });

        return back()->withNotify([['success', 'Suscripción renovada y pago registrado']]);
    }

    // ── Products CRUD ──

    public function productCreate($storeId)
    {
        $pageTitle = 'Agregar Producto';
        $store = Store::findOrFail($storeId);
        $categories = $store->categories;
        return view('admin.delivery.product_form', compact('pageTitle','store','categories'));
    }

    public function productEdit($id)
    {
        $pageTitle = 'Editar Producto';
        $product = Product::with('variations','addons')->findOrFail($id);
        $store = $product->store;
        $categories = $store->categories;
        return view('admin.delivery.product_form', compact('pageTitle','product','store','categories'));
    }

    public function productSave(Request $request, $id = null)
    {
        $data = $request->validate([
            'store_id'=>'required|exists:stores,id','store_category_id'=>'required|exists:store_categories,id',
            'name'=>'required','description'=>'nullable','price'=>'required|numeric',
            'discount_price'=>'nullable|numeric','sort_order'=>'nullable|integer',
            'status'=>'nullable','is_promoted'=>'nullable',
        ]);
        $data['status'] = $request->has('status') ? 1 : 0;
        $data['is_promoted'] = $request->has('is_promoted');

        if ($request->hasFile('image')) {
            $data['image'] = fileUploader($request->file('image'), 'assets/images/product');
        }

        if ($id) {
            $product = Product::findOrFail($id);
            $product->update($data);
        } else {
            $product = Product::create($data);
        }

        // Save variations
        if ($request->variation_name) {
            foreach ($request->variation_name as $i => $name) {
                if (empty($name)) continue;
                $varId = $request->variation_id[$i] ?? null;
                ProductVariation::updateOrCreate(
                    ['id'=>$varId, 'product_id'=>$product->id],
                    ['name'=>$name, 'price'=>$request->variation_price[$i]??0, 'status'=>1]
                );
            }
        }

        // Save addons
        if ($request->addon_name) {
            foreach ($request->addon_name as $i => $name) {
                if (empty($name)) continue;
                $addonId = $request->addon_id[$i] ?? null;
                ProductAddon::updateOrCreate(
                    ['id'=>$addonId, 'product_id'=>$product->id],
                    ['name'=>$name, 'price'=>$request->addon_price[$i]??0, 'status'=>1]
                );
            }
        }

        return redirect()->route('admin.delivery.stores')->withNotify([['success','Producto guardado']]);
    }

    public function productDelete($id)
    {
        Product::findOrFail($id)->delete();
        return back()->withNotify([['success', 'Producto eliminado']]);
    }

    // ── Store Categories CRUD ──

    public function storeCategorySave(Request $request, $storeId)
    {
        $request->validate(['name' => 'required|string|max:100', 'sort_order' => 'integer|min:0']);
        Store::findOrFail($storeId)->categories()->create([
            'name'       => $request->name,
            'sort_order' => $request->sort_order ?? 0,
            'status'     => 1,
        ]);
        return back()->withNotify([['success', 'Categoría creada']]);
    }

    public function storeCategoryUpdate(Request $request, $id)
    {
        $cat = StoreCategory::findOrFail($id);
        $cat->update($request->only(['name', 'sort_order']));
        return back()->withNotify([['success', 'Categoría actualizada']]);
    }

    public function storeCategoryToggle($id)
    {
        $cat = StoreCategory::findOrFail($id);
        $cat->update(['status' => $cat->status ? 0 : 1]);
        return back()->withNotify([['success', 'Estado actualizado']]);
    }

    public function storeCategoryDelete($id)
    {
        StoreCategory::findOrFail($id)->delete();
        return back()->withNotify([['success', 'Categoría eliminada']]);
    }

    // ── Refunds ──

    public function refunds()
    {
        $pageTitle = 'Reembolsos';
        $refunds = DeliveryRefund::with('user', 'order', 'favor')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.delivery.refunds', compact('pageTitle', 'refunds'));
    }

    public function refundAction(Request $request, $id)
    {
        $refund = DeliveryRefund::findOrFail($id);
        $action = $request->action; // approve or reject
        $refund->update([
            'status'       => $action === 'approve' ? 'approved' : 'rejected',
            'admin_remark' => $request->admin_remark,
        ]);

        if ($action === 'approve') {
            $user = $refund->user;
            if ($user) {
                $this->ensureWallet($user);
                $user->wallet->credit($refund->amount, 'refund', 'Reembolso aprobado #' . $refund->id, $refund);
            }
        }

        $notify[] = ['success', 'Reembolso ' . ($action === 'approve' ? 'aprobado' : 'rechazado')];
        return back()->withNotify($notify);
    }

    // ── Commissions ──

    public function commission()
    {
        $pageTitle = 'Configurar Comisiones';
        $commission = DeliveryCommission::first() ?? new DeliveryCommission();
        return view('admin.delivery.commission', compact('pageTitle', 'commission'));
    }

    public function commissionUpdate(Request $request)
    {
        $commission = DeliveryCommission::firstOrCreate([]);
        $commission->update($request->only([
            'delivery_percent', 'favor_percent', 'min_commission',
            'courier_commission_type', 'courier_fixed_amount',
            'store_commission_type', 'store_fixed_amount',
        ]));
        $notify[] = ['success', 'Comisión actualizada'];
        return back()->withNotify($notify);
    }

    // ── Solicitar Envío (Admin) ──
 
    public function requestForm()
    {
        $pageTitle = 'Solicitar Envío';
        $stores = Store::where('status', 1)->orderBy('name')->get();
        $activeDrivers = Driver::where('status', Status::ENABLE)
            ->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])
            ->count();
        $drivers = Driver::where('status', Status::ENABLE)
            ->whereIn('service_type', ['delivery', 'both'])
            ->orderBy('firstname')
            ->get();
        return view('admin.delivery.request', compact('pageTitle', 'stores', 'activeDrivers', 'drivers'));
    }

    public function requestFeeCalculate(Request $request)
    {
        [$pickupLat, $pickupLng] = DeliveryPricing::coordinateFromRequest($request, ['pickup_lat'], ['pickup_lng']);
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);

        if (!DeliveryPricing::hasCoordinates($pickupLat, $pickupLng) || !DeliveryPricing::hasCoordinates($deliveryLat, $deliveryLng)) {
            return response()->json(['status' => 'error', 'message' => 'Coordenadas incompletas'], 422);
        }

        $estimate = DeliveryPricing::estimateForPoints(
            $pickupLat,
            $pickupLng,
            $deliveryLat,
            $deliveryLng
        );
        return response()->json(array_merge(['status' => 'success'], $estimate));
    }

    public function requestStatus($id)
    {
        AdminDeliveryRequestDispatchService::processDueRequests();
        $favor = Favor::with('courier')->findOrFail($id);

        $courier = null;
        if ($favor->courier) {
            $driverLat = (float) $favor->courier->latitude;
            $driverLng = (float) $favor->courier->longitude;
            $pickupLat = (float) ($favor->pickup_lat ?: 0);
            $pickupLng = (float) ($favor->pickup_lng ?: 0);

            $distanceKm = '--';
            $timeMin = '--';

            if ($driverLat != 0 && $driverLng != 0 && $pickupLat != 0 && $pickupLng != 0) {
                try {
                    $est = \App\Support\DeliveryPricing::estimateForPoints($driverLat, $driverLng, $pickupLat, $pickupLng);
                    $distanceKm = $est['distance_km'] ?? '--';
                    $timeMin = $est['time_min'] ?? '--';
                } catch (\Exception $e) {}
            }

            $courier = [
                'name'        => $favor->courier->firstname . ' ' . $favor->courier->lastname,
                'phone'       => $favor->courier->mobile,
                'image'       => $favor->courier->image_src,
                'distance_km' => $distanceKm,
                'time_min'    => $timeMin,
            ];
        }

        return response()->json([
            'status'       => 'success',
            'favor_status' => $favor->status,
            'favor'        => [
                'id'              => $favor->id,
                'order_no'        => $favor->order_no,
                'pickup_address'  => $favor->pickup_address,
                'delivery_address'=> $favor->delivery_address,
                'delivery_fee'    => $favor->delivery_fee,
                'status'          => $favor->status,
                'courier_id'      => $favor->courier_id,
            ],
            'courier' => $courier,
            'dispatch' => [
                'mode'       => $favor->dispatch_mode,
                'driver_name'=> $favor->dispatch_mode === 'admin_targeted' ? $favor->courier?->fullname : null,
                'expires_at' => $favor->dispatch_timeout_at?->toIso8601String(),
            ],
        ]);
    }

    public function requestSubmit(Request $request)
    {
        $request->validate([
            'store_id'         => 'required|exists:stores,id',
            'delivery_address' => 'required|string|max:500',
            'delivery_lat'     => 'required|numeric',
            'delivery_lng'     => 'required|numeric',
            'description'      => 'required|string|max:1000',
            'recipient_phone'  => 'nullable|string|max:20',
            'driver_id'        => 'nullable|string',
        ]);

        $store = Store::findOrFail($request->store_id);
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);

        $estimate = DeliveryPricing::estimateForPoints(
            (float) $store->latitude,
            (float) $store->longitude,
            $deliveryLat,
            $deliveryLng
        );

        $deliveryFee = $estimate['delivery_fee'] ?? 5;
        if (isset($estimate['distance_km']) && $estimate['distance_km'] < 1.0) {
            $deliveryFee = 4.0;
        }
        $total = $deliveryFee;
        $orderNo = 'ENV-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(5));

        $driverId = $request->driver_id;
        $assignedDriver = null;
        if ($driverId && $driverId !== 'all') {
            $assignedDriver = Driver::where('status', Status::ENABLE)
                ->whereIn('service_type', ['delivery', 'both'])
                ->findOrFail($driverId);
        }

        $favor = Favor::create([
            'order_no'         => $orderNo,
            'user_id'          => null,
            'type'             => 'send',
            'description'      => $request->description,
            'store_name'       => $store->name,
            'store_address'    => $store->address,
            'estimated_amount' => 0,
            'pickup_address'   => $store->address,
            'pickup_lat'       => $store->latitude,
            'pickup_lng'       => $store->longitude,
            'delivery_address' => $request->delivery_address,
            'delivery_lat'     => $deliveryLat,
            'delivery_lng'     => $deliveryLng,
            'recipient_name'   => $request->recipient_name ?? 'Cliente',
            'recipient_phone'  => $request->recipient_phone,
            'delivery_fee'     => $deliveryFee,
            'total'            => $total,
            'status'           => $assignedDriver ? 'accepted' : 'searching_courier',
            'courier_id'       => $assignedDriver ? $assignedDriver->id : null,
            'courier_assigned_at' => $assignedDriver ? now() : null,
            'dispatch_mode'    => $assignedDriver ? 'admin_direct' : 'admin_broadcast',
            'dispatch_timeout_at' => $assignedDriver ? null : now()->addSeconds(AdminDeliveryRequestDispatchService::INITIAL_BROADCAST_WAIT_SECONDS),
            'dispatch_attempted_driver_ids' => [],
            'payment_method_code' => 0,
        ]);

        try {

            // Check push notifications are enabled
            if (!gs('pn')) {
                $notify[] = ['warning', 'Solicitud #' . $orderNo . ' creada pero las notificaciones push están desactivadas en configuración general.'];
                return redirect()->route('admin.delivery.favors')->withNotify($notify);
            }

            // Check firebase config
            $fbConfig = gs('firebase_config');
            if (!$fbConfig || empty($fbConfig->projectId)) {
                $notify[] = ['warning', 'Solicitud #' . $orderNo . ' creada pero Firebase no está configurado.'];
                return redirect()->route('admin.delivery.favors')->withNotify($notify);
            }

            // Check push_config.json exists
            $pushConfigPath = getFilePath('pushConfig') . '/push_config.json';
            if (!file_exists($pushConfigPath)) {
                $notify[] = ['warning', 'Solicitud #' . $orderNo . ' creada. Falta el archivo push_config.json en ' . $pushConfigPath];
                return redirect()->route('admin.delivery.favors')->withNotify($notify);
            }

            if ($assignedDriver) {
                $sent = FcmService::sendToDriver(
                    $assignedDriver,
                    'Nuevo envío asignado',
                    'Se te ha asignado un envío en ' . $store->name . ' — S/ ' . number_format($deliveryFee, 2),
                    [
                        'type'    => 'new_delivery_request',
                        'favor_id' => (string) $favor->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );

                if ($sent) {
                    $notify[] = ['success', 'Solicitud #' . $orderNo . ' asignada y enviada al repartidor ' . $assignedDriver->fullname];
                } else {
                    $notify[] = ['warning', 'Solicitud #' . $orderNo . ' asignada al repartidor ' . $assignedDriver->fullname . ' pero falló el envío de la notificación push.'];
                }
            } else {
                $sent = FcmService::sendToAllCouriers(
                    'Nuevo envío disponible',
                    'Recoger en ' . $store->name . ' — S/ ' . number_format($deliveryFee, 2),
                    [
                        'type'    => 'new_delivery_request',
                        'favor_id' => (string) $favor->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );

                $driverCount = Driver::where('status', Status::ENABLE)
                    ->whereIn('service_type', ['delivery', 'both'])
                    ->count();

                if ($sent) {
                    $notify[] = ['success', 'Solicitud #' . $orderNo . ' enviada a ' . $driverCount . ' repartidores'];
                } else {
                    $notify[] = ['warning', 'Solicitud #' . $orderNo . ' creada pero ningún repartidor tiene tokens de dispositivo registrados.'];
                }
            }
        } catch (\Throwable $e) {
            $notify[] = ['warning', 'Solicitud creada pero falló la notificación push: ' . $e->getMessage()];
        }

        if (\Request::expectsJson() || \Request::isJson()) {
            return response()->json([
                'status'    => 'success',
                'favor_id'  => $favor->id,
                'order_no'  => $orderNo,
                'message'   => 'Solicitud creada con éxito.',
            ]);
        }

        return redirect()->route('admin.delivery.favors')->withNotify($notify);
    }

    // ── Wallets ──

    public function wallets()
    {
        $pageTitle = 'Billeteras';
        $wallets = Wallet::with('holder')->orderBy('balance', 'desc')->paginate(getPaginate());
        
        foreach ($wallets as $w) {
            $w->holder_balance = null;
            $w->trx_balance = null;
            
            if ($w->holder_type === 'App\Models\Driver') {
                if ($w->holder) {
                    $w->holder_balance = $w->holder->balance;
                    $w->trx_balance = \App\Models\Transaction::where('driver_id', $w->holder_id)
                        ->sum(\DB::raw("CASE WHEN trx_type = '+' THEN amount WHEN trx_type = '-' THEN -amount ELSE 0 END"));
                }
            } elseif ($w->holder_type === 'App\Models\User') {
                if ($w->holder) {
                    $w->holder_balance = $w->holder->balance;
                    $w->trx_balance = \App\Models\Transaction::where('user_id', $w->holder_id)
                        ->sum(\DB::raw("CASE WHEN trx_type = '+' THEN amount WHEN trx_type = '-' THEN -amount ELSE 0 END"));
                }
            }
        }
        
        return view('admin.delivery.wallets', compact('pageTitle', 'wallets'));
    }

    public function walletDetail($id)
    {
        $pageTitle = 'Detalle de Billetera';
        $wallet = Wallet::with('holder', 'transactions')->findOrFail($id);
        return view('admin.delivery.wallet_detail', compact('pageTitle', 'wallet'));
    }

    public function addBalance(Request $request)
    {
        $request->validate([
            'holder_type' => 'required|in:user,driver,seller',
            'holder_id'   => 'required|integer',
            'amount'      => 'required|numeric|min:0.01',
            'remark'      => 'nullable|string',
        ]);

        $model = match($request->holder_type) {
            'user'   => User::findOrFail($request->holder_id),
            'driver' => Driver::findOrFail($request->holder_id),
            'seller' => Seller::findOrFail($request->holder_id),
        };

        $isLegacy = in_array($request->holder_type, ['user', 'driver']);

        if ($isLegacy) {
            // Fuente de verdad: columna balance + tabla transactions (lo que lee la app)
            $model->balance += $request->amount;
            $model->save();

            $trx = new Transaction();
            $trx->trx_type     = '+';
            $trx->amount       = $request->amount;
            $trx->post_balance = $model->balance;
            $trx->charge       = 0;
            $trx->trx          = getTrx();
            $trx->remark       = $request->remark ?? 'bonus';
            $trx->details      = 'Agregado por admin';
            if ($request->holder_type === 'user')   $trx->user_id   = $model->id;
            if ($request->holder_type === 'driver') $trx->driver_id = $model->id;
            $trx->save();
        }

        // Sync wallet
        $this->ensureWallet($model);
        $model->wallet->credit($request->amount, $request->remark ?? 'bonus', 'Agregado por admin');

        $saldo = $isLegacy ? $model->balance : $model->wallet->balance;
        $body = 'Se ha agregado S/ ' . number_format($request->amount, 2) . ' a tu billetera. Nuevo saldo: S/ ' . number_format($saldo, 2);

        try {
            match($request->holder_type) {
                'user'   => FcmService::sendToUser($model, 'Saldo agregado', $body, ['type' => 'wallet_topup', 'amount' => (string) $request->amount]),
                'driver' => FcmService::sendToDriver($model, 'Saldo agregado', $body, ['type' => 'wallet_topup', 'amount' => (string) $request->amount]),
                'seller' => FcmService::sendToSeller($model, 'Saldo agregado', $body, ['type' => 'wallet_topup', 'amount' => (string) $request->amount]),
            };
        } catch (\Exception $e) {}

        $notify[] = ['success', 'Saldo agregado correctamente'];
        return back()->withNotify($notify);
    }

    public function deductBalance(Request $request)
    {
        $request->validate([
            'holder_type' => 'required|in:user,driver,seller',
            'holder_id'   => 'required|integer',
            'amount'      => 'required|numeric|min:0.01',
            'remark'      => 'nullable|string',
        ]);

        $model = match($request->holder_type) {
            'user'   => User::findOrFail($request->holder_id),
            'driver' => Driver::findOrFail($request->holder_id),
            'seller' => Seller::findOrFail($request->holder_id),
        };

        $isLegacy = in_array($request->holder_type, ['user', 'driver']);
        $currentBalance = $isLegacy ? $model->balance : ($model->wallet?->balance ?? 0);

        if ($currentBalance < $request->amount) {
            $notify[] = ['error', 'Saldo insuficiente'];
            return back()->withNotify($notify);
        }

        if ($isLegacy) {
            // Fuente de verdad: columna balance + tabla transactions
            $model->balance -= $request->amount;
            $model->save();

            $trx = new Transaction();
            $trx->trx_type     = '-';
            $trx->amount       = $request->amount;
            $trx->post_balance = $model->balance;
            $trx->charge       = 0;
            $trx->trx          = getTrx();
            $trx->remark       = $request->remark ?? 'deduction';
            $trx->details      = 'Deducido por admin';
            if ($request->holder_type === 'user')   $trx->user_id   = $model->id;
            if ($request->holder_type === 'driver') $trx->driver_id = $model->id;
            $trx->save();
        }

        // Sync wallet
        $this->ensureWallet($model);
        $model->wallet->debit($request->amount, $request->remark ?? 'deduction', 'Deducido por admin');

        $notify[] = ['success', 'Saldo deducido correctamente'];
        return back()->withNotify($notify);
    }

    public function walletTransactions($walletId)
    {
        $pageTitle = 'Transacciones';
        $wallet = Wallet::findOrFail($walletId);
        $transactions = $wallet->transactions()->paginate(getPaginate());
        return view('admin.delivery.wallet_transactions', compact('pageTitle', 'wallet', 'transactions'));
    }

    public function getHoldersByType(Request $request)
    {
        $type = $request->type;
        $search = $request->search ?? '';

        $results = match($type) {
            'user'   => User::where('status', 1)
                ->where(fn($q) => $q->where('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%"))
                ->selectRaw("id, CONCAT(firstname, ' ', lastname, ' (', email, ')') as text")
                ->limit(50)->get(),
            'driver' => Driver::where('status', 1)
                ->where(fn($q) => $q->where('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%"))
                ->selectRaw("id, CONCAT(firstname, ' ', lastname, ' (', COALESCE(mobile,email), ')') as text")
                ->limit(50)->get(),
            'seller' => Seller::where('status', 1)
                ->where(fn($q) => $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%"))
                ->selectRaw("id, CONCAT(name, ' (', email, ')') as text")
                ->limit(50)->get(),
            default => collect([]),
        };

        return response()->json(['results' => $results]);
    }

    // ── Withdrawal Management ──

    public function withdrawals(Request $request)
    {
        $pageTitle = 'Gestión de Retiros';
        $status = $request->status;

        $withdrawals = Withdrawal::with(['driver', 'method'])
            ->when($status === 'pending', fn($q) => $q->pending())
            ->when($status === 'approved', fn($q) => $q->approved())
            ->when($status === 'rejected', fn($q) => $q->rejected())
            ->when(!$status, fn($q) => $q->where('status', '!=', Status::PAYMENT_INITIATE))
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        $pendingCount  = Withdrawal::pending()->count();
        $approvedCount = Withdrawal::approved()->count();
        $rejectedCount = Withdrawal::rejected()->count();

        return view('admin.delivery.withdrawals', compact(
            'pageTitle', 'withdrawals', 'status', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    public function withdrawalApprove(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $withdrawal = Withdrawal::pending()->findOrFail($request->id);
        $withdrawal->update(['status' => Status::PAYMENT_SUCCESS]);

        $notify[] = ['success', 'Retiro aprobado exitosamente'];
        return back()->withNotify($notify);
    }

    public function withdrawalReject(Request $request)
    {
        $request->validate([
            'id'   => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);
        $withdrawal = Withdrawal::pending()->findOrFail($request->id);

        // Refund the wallet if exists
        if ($withdrawal->user_type && $withdrawal->user_id) {
            $wallet = Wallet::where('holder_type', $withdrawal->user_type)
                ->where('holder_id', $withdrawal->user_id)
                ->first();
            if ($wallet) {
                $wallet->credit($withdrawal->amount, 'refund', 'Retiro rechazado - reembolso');
            }
        } elseif ($withdrawal->driver_id) {
            $driver = Driver::find($withdrawal->driver_id);
            if ($driver) {
                $this->ensureWallet($driver);
                $driver->wallet->credit($withdrawal->amount, 'refund', 'Retiro rechazado - reembolso');
            }
        }

        $withdrawal->update([
            'status'            => Status::PAYMENT_REJECT,
            'rejection_reason'  => $request->reason ?? 'Rechazado por administrador',
        ]);

        $notify[] = ['success', 'Retiro rechazado y saldo reembolsado'];
        return back()->withNotify($notify);
    }

    public function sectionsConfig()
    {
        $pageTitle = 'Configurar Secciones Premium Delivery';
        $general = gs();
        $sections = $general->delivery_sections_config;

        if (!$sections || !is_array($sections)) {
            $sections = [
                ['key' => 'populares_cerca_ti', 'title' => 'Populares cerca de ti', 'subtitle' => 'Restaurantes favoritos en tu zona', 'is_enabled' => 1, 'sort_order' => 1],
                ['key' => 'los_mas_vendidos', 'title' => 'Los más vendidos', 'subtitle' => 'Lo que la gente está pidiendo más', 'is_enabled' => 1, 'sort_order' => 2],
                ['key' => 'marcas_descuento', 'title' => 'Marcas con descuento', 'subtitle' => 'Ahorra con súper marcas hoy', 'is_enabled' => 1, 'sort_order' => 3],
                ['key' => 'recomendados_ti', 'title' => 'Recomendados para ti', 'subtitle' => 'Nuestra selección especial', 'is_enabled' => 1, 'sort_order' => 4],
                ['key' => 'cuidamos_bolsillo', 'title' => 'Cuidamos tu bolsillo', 'subtitle' => 'Envíos gratis y opciones económicas', 'is_enabled' => 1, 'sort_order' => 5],
                ['key' => 'promos_cerca_ti', 'title' => 'Promos cerca de ti', 'subtitle' => 'Grandes ofertas a un clic', 'is_enabled' => 1, 'sort_order' => 6],
            ];
        }

        return view('admin.delivery.sections_config', compact('pageTitle', 'sections'));
    }

    public function sectionsConfigUpdate(Request $request)
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.key' => 'required|string',
            'sections.*.title' => 'required|string',
            'sections.*.subtitle' => 'nullable|string',
            'sections.*.sort_order' => 'required|integer',
        ]);

        $general = gs();
        $sections = [];

        foreach ($request->sections as $sec) {
            $sections[] = [
                'key' => $sec['key'],
                'title' => $sec['title'],
                'subtitle' => $sec['subtitle'] ?? '',
                'is_enabled' => isset($sec['is_enabled']) ? 1 : 0,
                'sort_order' => (int) $sec['sort_order'],
            ];
        }

        usort($sections, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $general->delivery_sections_config = $sections;
        $general->save();

        $notify[] = ['success', 'Secciones premium del home actualizadas correctamente'];
        return back()->withNotify($notify);
    }

    // ── Helpers ──

    private function ensureWallet($holder)
    {
        if (!$holder->wallet) {
            $wallet = Wallet::create(['holder_type' => get_class($holder), 'holder_id' => $holder->id]);
            $holder->update(['wallet_id' => $wallet->id]);
            $holder->setRelation('wallet', $wallet);
        } elseif (!$holder->wallet_id) {
            $holder->update(['wallet_id' => $holder->wallet->id]);
        }
        return $holder->wallet;
    }

    private function processCommission($order)
    {
        $commission = DeliveryCommission::first();
        $percent = $commission?->delivery_percent ?? 10;
        $deliveryFee = (float) ($order->delivery_fee ?? 0);
        $amount  = max($deliveryFee * $percent / 100, $commission?->min_commission ?? 1);
        $order->update(['commission_amount' => $amount]);

        if ($order->driver) {
            $earning = CourierEarning::firstOrCreate([
                'courier_id' => $order->driver->id,
                'job_type' => DeliveryOrder::class,
                'job_id' => $order->id,
            ], [
                'amount' => $deliveryFee,
                'commission' => $amount,
                'description' => 'Entrega de pedido #' . $order->order_no,
            ]);
            if ($earning->wasRecentlyCreated) {
                DeliveryFinancialLedger::recordDriverEarning($order->driver, $deliveryFee, $earning);
                $this->ensureWallet($order->driver);
                $order->driver->wallet->debit($amount, 'commission', 'Comisión pedido #' . $order->order_no, $order);
                $order->driver->balance = $order->driver->wallet->balance;
                $order->driver->save();
            }
            if (DeliveryFinancialLedger::paymentChannel($order) === 'cash') {
                DeliveryFinancialLedger::recordCashCollection($order->driver, (float) $order->total, $order);
            }
        }

        if ($order->store?->seller) {
            $subtotal = (float) $order->subtotal;
            $sellerCommission = ($commission?->store_commission_type ?? 'percent') === 'percent'
                ? $subtotal * (float) ($commission?->store_commission_percent ?? 5) / 100
                : (float) ($commission?->store_fixed_amount ?? 0);
            DeliveryFinancialLedger::recordSellerReceivable(
                $order->store->seller,
                max(0, $subtotal - $sellerCommission),
                DeliveryFinancialLedger::paymentChannel($order),
                $order
            );
        }
    }

    private function processFavorCommission($favor)
    {
        $commission = DeliveryCommission::first();
        $percent = $commission?->favor_percent ?? 15;
        $amount  = max($favor->total * $percent / 100, $commission?->min_commission ?? 1);
        $favor->update(['commission_amount' => $amount]);

        if ($favor->courier) {
            $earning = CourierEarning::firstOrCreate([
                'courier_id' => $favor->courier->id,
                'job_type' => Favor::class,
                'job_id' => $favor->id,
            ], [
                'amount' => (float) $favor->total,
                'commission' => $amount,
                'description' => 'Entrega de favor #' . $favor->order_no,
            ]);
            if ($earning->wasRecentlyCreated) {
                DeliveryFinancialLedger::recordDriverEarning($favor->courier, (float) $favor->total, $earning);
                $this->ensureWallet($favor->courier);
                $favor->courier->wallet->debit($amount, 'commission', 'Comisión favor #' . $favor->order_no, $favor);
                $favor->courier->balance = $favor->courier->wallet->balance;
                $favor->courier->save();
            }
            if (DeliveryFinancialLedger::paymentChannel($favor) === 'cash') {
                DeliveryFinancialLedger::recordCashCollection($favor->courier, (float) $favor->total, $favor);
            }
        }
    }

    // ── Business Packages Management ──

    public function packages()
    {
        $pageTitle = 'Paquetes Empresariales';
        $packages = BusinessPackage::orderBy('sort_order')->get();
        $subscriptions = StorePackage::with('store', 'seller', 'package')->latest()->paginate(20);
        return view('admin.delivery.packages', compact('pageTitle', 'packages', 'subscriptions'));
    }

    public function packageSave(Request $request, $id = null)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'price'         => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'type'          => 'required|string',
        ]);

        $data = $request->only(['name', 'price', 'duration_days', 'type', 'description', 'icon']);
        $data['slug'] = \Str::slug($request->name);
        $data['features'] = $request->features ?? [];
        $data['sort_order'] = $request->sort_order ?? 0;
        $data['status'] = $request->status ? 1 : 0;

        if ($id) {
            BusinessPackage::findOrFail($id)->update($data);
        } else {
            BusinessPackage::create($data);
        }

        return back()->withNotify([['success', 'Paquete guardado']]);
    }

    public function packageStatus($id)
    {
        $pkg = BusinessPackage::findOrFail($id);
        $pkg->update(['status' => $pkg->status ? 0 : 1]);
        return back()->withNotify([['success', 'Estado actualizado']]);
    }

    public function packageDelete($id)
    {
        BusinessPackage::findOrFail($id)->delete();
        return back()->withNotify([['success', 'Paquete eliminado']]);
    }

    public function subscriptionApprove($id)
    {
        $sub = StorePackage::findOrFail($id);
        $pkg = $sub->package;
        $sub->update([
            'status'    => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays($pkg->duration_days),
        ]);
        return back()->withNotify([['success', 'Suscripción activada']]);
    }

    public function subscriptionReject($id)
    {
        StorePackage::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->withNotify([['success', 'Suscripción cancelada']]);
    }

    // ── Bulk Product Upload ──

    public function bulkUpload()
    {
        $pageTitle = 'Carga Masiva de Productos';
        $stores = Store::with('seller')->orderBy('name')->get();
        return view('admin.delivery.bulk', compact('pageTitle', 'stores'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Categoría');
        $sheet->setCellValue('B1', 'Nombre del Producto');
        $sheet->setCellValue('C1', 'Descripción');
        $sheet->setCellValue('D1', 'Precio (S/)');
        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '16A34A']]];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $samples = [['ENTRADAS','Piqueo charapita','6 canastitas con chorizo',17.00],['PLATOS','Juane de gallina','Arroz con gallina en bijao',15.00],['BEBIDAS','Limonada frozen','Limonada frappé',8.00]];
        foreach ($samples as $i => $s) { $r = $i + 2; $sheet->setCellValue('A'.$r,$s[0]); $sheet->setCellValue('B'.$r,$s[1]); $sheet->setCellValue('C'.$r,$s[2]); $sheet->setCellValue('D'.$r,$s[3]); $sheet->getStyle('D'.$r)->getNumberFormat()->setFormatCode('#,##0.00'); }
        $sheet->getColumnDimension('A')->setWidth(18); $sheet->getColumnDimension('B')->setWidth(30); $sheet->getColumnDimension('C')->setWidth(40); $sheet->getColumnDimension('D')->setWidth(14);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="plantilla-productos-liztogo.xlsx"');
        $writer->save('php://output'); exit;
    }

    public function bulkStore(Request $request)
    {
        $store = Store::findOrFail($request->store_id);
        $items = $request->items ?? [];
        if (!is_array($items) || count($items) === 0) {
            return response()->json(['status' => 'error', 'message' => 'Sin productos para guardar']);
        }

        $created = 0;
        $categoryMap = [];
        $details = [];

        foreach ($items as $item) {
            $catName = trim($item['category'] ?? 'General');
            $name = trim($item['name'] ?? '');
            $price = floatval($item['price'] ?? 0);
            $desc = trim($item['description'] ?? '');

            if (!$name || $price <= 0) continue;

            if (!isset($categoryMap[$catName])) {
                $normalizedCat = mb_strtolower(trim($catName), 'UTF-8');
                $existingCat = StoreCategory::where('store_id', $store->id)
                    ->whereRaw('LOWER(name) = ?', [$normalizedCat])
                    ->first();

                if ($existingCat) {
                    $categoryMap[$catName] = $existingCat->id;
                    $catName = $existingCat->name;
                } else {
                    $cat = StoreCategory::create([
                        'store_id' => $store->id, 'name' => $catName,
                        'sort_order' => count($categoryMap), 'status' => 1,
                    ]);
                    $categoryMap[$catName] = $cat->id;
                }
                if (!isset($details[$catName])) {
                    $details[$catName] = ['category' => $catName, 'count' => 0, 'items' => []];
                }
            }

            $exists = Product::where('store_id', $store->id)
                ->where('store_category_id', $categoryMap[$catName])
                ->where('name', $name)->exists();
            if ($exists) continue;

            Product::create([
                'store_id' => $store->id, 'store_category_id' => $categoryMap[$catName],
                'name' => $name, 'description' => $desc, 'price' => $price,
                'sort_order' => $details[$catName]['count'], 'status' => 1,
            ]);

            $details[$catName]['count']++;
            $details[$catName]['items'][] = ['name' => $name, 'price' => $price];
            $created++;
        }

        return response()->json([
            'status' => 'success',
            'created' => $created,
            'categories' => count($categoryMap),
            'details' => array_values($details),
        ]);
    }

    public function ocrParse(Request $request)
    {
        $request->validate(['image' => 'required|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,xlsx,xls,csv']);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $items = [];

        if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $items = $this->parseExcelColumns($file->getPathname(), $ext);
        } else {
            $imageBase64 = base64_encode(file_get_contents($file->getPathname()));
            $extractedText = '';
            $visionKey = gs('google_vision_api_key');
            if ($visionKey) {
                try {
                    $payload = json_encode(['requests' => [['image' => ['content' => $imageBase64], 'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]]]]]);
                    $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key=' . $visionKey);
                    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 30]);
                    $result = json_decode(curl_exec($ch), true);
                    curl_close($ch);
                    $extractedText = $result['responses'][0]['textAnnotations'][0]['description'] ?? '';
                } catch (\Exception $e) {}
            }
            $items = $this->parseMenuText($extractedText);
        }

        return response()->json(['status' => 'success', 'raw_text' => $extractedText ?? '', 'items' => $items]);
    }

    private function parseExcelColumns($path, $ext)
    {
        $items = [];
        try {
            $rows = $ext === 'csv' ? array_map('str_getcsv', file($path)) : \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
            $startRow = 0;
            if (count($rows) > 0) {
                $first = array_map('trim', $rows[0]);
                if (stripos($first[0] ?? '', 'categor') !== false || stripos($first[1] ?? '', 'nombre') !== false) $startRow = 1;
            }
            $currentCategory = 'General';
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = array_map('trim', $rows[$i]);
                $colA = $row[0] ?? ''; $colB = $row[1] ?? ''; $colC = $row[2] ?? ''; $colD = $row[3] ?? '';
                if (empty($colA) && empty($colB) && empty($colC) && empty($colD)) continue;
                if (!empty($colA) && empty($colB) && empty($colC) && empty($colD)) { $currentCategory = trim($colA); continue; }
                $name = trim($colB);
                $price = floatval(str_replace([',','S/','S/ ',' '], ['','','','.'], $colD));
                if (!empty($colA) && !empty($colB)) $currentCategory = trim($colA);
                if (!empty($name) && $price > 0) $items[] = ['category' => $currentCategory, 'name' => $name, 'description' => trim($colC), 'price' => $price];
            }
        } catch (\Exception $e) {}
        return $items;
    }

    private function parseMenuText($text)
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $items = []; $currentCategory = 'General';

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if (empty($line)) continue;

            $hasPrice = preg_match('/(?:S\/\s*)?(\d+[.,]\d{2}|\d+)\s*$/', $line, $priceMatch);
            $price = $hasPrice ? (float) str_replace(',', '.', $priceMatch[1]) : 0;
            $cleanLine = preg_replace('/[^\w\sáéíóúñÁÉÍÓÚÑ]/u', '', $line);
            $isAllCaps = strtoupper($cleanLine) === $cleanLine && strlen($cleanLine) > 2 && strlen($cleanLine) < 45;
            $isShortNoNum = str_word_count($cleanLine) <= 3 && strlen($cleanLine) < 45 && !preg_match('/\d{4,}/', $line) && !$hasPrice;

            if ($isAllCaps || ($isShortNoNum && !$hasPrice)) {
                $currentCategory = ucfirst(mb_strtolower($cleanLine, 'UTF-8'));
                continue;
            }

            if ($hasPrice && $price > 0) {
                $name = trim(preg_replace('/\s+(?:S\/\s*)?\d+[.,]\d{0,2}\s*$/', '', $line));
                $desc = '';
                $nameWords = explode(' ', $name);
                if (count($nameWords) > 5 && strlen($name) > 35) {
                    $name = implode(' ', array_slice($nameWords, 0, 4));
                    $desc = implode(' ', array_slice($nameWords, 4));
                }
                if (!empty($name)) {
                    $items[] = ['category' => $currentCategory, 'name' => $name, 'description' => $desc, 'price' => $price];
                }
            }
        }
        return $items;
    }

    // ── Coupons ──

    public function coupons()
    {
        $pageTitle = 'Cupones y Descuentos';
        $coupons = Coupon::latest()->get();
        $usages = CouponUsage::with('user', 'coupon')->latest()->limit(30)->get();
        return view('admin.delivery.coupons', compact('pageTitle', 'coupons', 'usages'));
    }

    public function couponStore(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'name' => 'required',
            'type' => 'required|in:percentage,fixed,free_delivery',
            'value' => 'required_if:type,percentage,fixed|nullable|numeric|min:0'
        ]);
        $data = $request->only(['code','name','type','value','min_order','max_discount','usage_limit','per_user_limit','starts_at','expires_at','description']);
        if ($request->type === 'free_delivery') {
            $data['value'] = 0;
        }
        Coupon::create($data + ['status' => 1]);
        return back()->withNotify([['success', 'Cupón creado']]);
    }

    public function couponUpdate(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code,' . $id,
            'name' => 'required',
            'type' => 'required|in:percentage,fixed,free_delivery',
            'value' => 'required_if:type,percentage,fixed|nullable|numeric|min:0'
        ]);
        $data = $request->only(['code','name','type','value','min_order','max_discount','usage_limit','per_user_limit','starts_at','expires_at','description','status']);
        if ($request->type === 'free_delivery') {
            $data['value'] = 0;
        }
        Coupon::findOrFail($id)->update($data);
        return back()->withNotify([['success', 'Cupón actualizado']]);
    }

    public function couponDelete($id) { Coupon::findOrFail($id)->delete(); return back()->withNotify([['success','Cupón eliminado']]); }

    // ── Free Deliveries ──

    public function freeDeliveries()
    {
        $pageTitle = 'Envíos Gratis';
        $deliveries = FreeDelivery::with('user')->latest()->get();
        $users = \App\Models\User::where('status', 1)->orderBy('firstname')->get();
        return view('admin.delivery.free_deliveries', compact('pageTitle', 'deliveries', 'users'));
    }

    public function freeDeliveryStore(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id', 'remaining' => 'required|integer|min:1']);
        FreeDelivery::create($request->only(['user_id','remaining','notes','status']));
        return back()->withNotify([['success', 'Envío gratis asignado']]);
    }

    public function freeDeliveryDelete($id) { FreeDelivery::findOrFail($id)->delete(); return back()->withNotify([['success','Eliminado']]); }

    public function favorLiveLocation($id)
    {
        $favor = Favor::with('courier')->find($id);
        if (!$favor || !$favor->courier) {
            return response()->json(['error' => 'Favor or courier not found'], 404);
        }
        return response()->json([
            'success' => true,
            'latitude' => $favor->courier->current_lat,
            'longitude' => $favor->courier->current_lot,
            'bearing' => $favor->courier->bearing ?? null
        ]);
    }
}
