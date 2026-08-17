<?php

namespace App\Http\Controllers;

use App\Events\DeliveryOrderStatusUpdated;
use App\Events\NewJobAvailable;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\InvItem;
use App\Models\InvKardex;
use App\Models\InvProductItem;
use App\Models\InvPurchase;
use App\Models\InvPurchaseItem;
use App\Models\InvSupplier;
use App\Models\PosArea;
use App\Models\PosBankAccount;
use App\Models\PosCashSession;
use App\Models\PosExpense;
use App\Models\PosInvoiceSeries;
use App\Models\PosInvoiceType;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosStaff;
use App\Models\PosStaffAttendance;
use App\Models\PosStaffPayroll;
use App\Models\PosTable;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariation;
use App\Models\Seller;
use App\Models\SellerCompany;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StorePackage;
use App\Models\StorePackagePayment;
use App\Models\SunatInvoice;
use App\Models\Zone;
use App\Support\DeliveryPricing;
use App\Services\FcmService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;

class SellerPosController extends Controller
{


    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $excluded = ['seller.login', 'seller.web.login', 'seller.token.login', 'seller.register', 'seller.broadcasting.auth'];
            if (in_array($request->route()->getName(), $excluded)) {
                return $next($request);
            }
            $sellerId = Session::get('seller_id');
            if (!$sellerId || !\App\Models\Seller::find($sellerId)) {
                Session::forget('seller_id');
                Session::forget('seller_staff_id');
                return redirect()->route('seller.login');
            }

            // Authorization check for staff members
            $staffId = Session::get('seller_staff_id');
            if ($staffId) {
                $staff = \App\Models\PosStaff::find($staffId);
                if (!$staff || $staff->status !== 'active') {
                    Session::forget('seller_staff_id');
                    Session::forget('seller_id');
                    return redirect()->route('seller.login')->with('error', 'Sesión de empleado no válida.');
                }

                // Force their company context
                if ($staff->seller_company_id) {
                    Session::put('active_company_id', $staff->seller_company_id);
                }

                // Protect specific routes based on permissions
                $routeName = $request->route()->getName();
                
                // Route mapping to permission keys
                $permissionsMap = [
                    'seller.pos' => 'pos_orders',
                    'seller.pos.floorplan' => 'pos_orders',
                    'seller.pos.tables' => 'pos_orders',
                    'seller.pos.kitchen' => 'kitchen',
                    'seller.pos.billing' => 'billing',
                    'seller.cash' => 'billing',
                    'seller.cash.open' => 'billing',
                    'seller.cash.close' => 'billing',
                    'seller.cash.arqueo' => 'billing',
                    'seller.cash.transaction' => 'billing',
                    'seller.expenses' => 'billing',
                    'seller.expenses.store' => 'billing',
                    'seller.pos.bank_accounts' => 'billing',
                    'seller.products' => 'products',
                    'seller.categories' => 'products',
                    'seller.products.bulk' => 'products',
                    'seller.products.bulk.store' => 'products',
                    'seller.products.bulk.template' => 'products',
                    'seller.qrmenu' => 'products',
                    'seller.customers' => 'pos_orders',
                    'seller.orders' => 'pos_orders',
                    'seller.orders.cancel' => 'pos_orders',
                    'seller.orders.status' => 'pos_orders',
                    'seller.external.order' => 'pos_orders',
                    'seller.inventory.items' => 'inventory',
                    'seller.logistics.warehouses' => 'inventory',
                    'seller.logistics.suppliers' => 'inventory',
                    'seller.logistics.purchase_orders' => 'inventory',
                    'seller.logistics.receptions' => 'inventory',
                    'seller.inventory.purchases' => 'inventory',
                    'seller.inventory.recipes' => 'inventory',
                    'seller.logistics.transfers' => 'inventory',
                    'seller.inventory.wastes' => 'inventory',
                    'seller.inventory.sales' => 'inventory',
                    'seller.inventory.kardex' => 'inventory',
                    'seller.logistics.reports' => 'inventory',
                    'seller.inventory.tax-report' => 'inventory',
                    'seller.pos.staff' => 'hr',
                    'seller.pos.hr.attendance' => 'hr',
                    'seller.pos.hr.payroll' => 'hr',
                    'seller.pos.staff.reports' => 'reports',
                    'seller.reports' => 'reports',
                    'seller.reports.advanced' => 'reports',
                    'seller.reports.export' => 'reports',
                    'seller.invoicing' => 'settings',
                    'seller.sunat.config' => 'settings',
                    'seller.invoice.series' => 'settings',
                    'seller.delivery' => 'settings',
                    'seller.api.settings' => 'settings',
                    'seller.api.token' => 'settings',
                    'seller.notifications' => 'notifications',
                    'seller.notifications.send' => 'notifications',
                ];

                foreach ($permissionsMap as $routePrefix => $permissionKey) {
                    if ($routeName === $routePrefix || str_starts_with($routeName, $routePrefix . '.')) {
                        if (!$staff->hasPermission($permissionKey)) {
                            if (in_array($permissionKey, ['reports', 'settings']) && $staff->hasPermission('accounting')) {
                                continue;
                            }
                            if ($request->expectsJson()) {
                                return response()->json(['status' => false, 'result' => 'No tienes permiso para realizar esta acción.'], 403);
                            }
                            return redirect()->route('seller.pos')->with('error', 'No tienes permisos para acceder a esta sección.');
                        }
                    }
                }
            }
            return $next($request);
        });
    }

    public function broadcastingAuth(Request $request)
    {
        $socketId = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        if (!$socketId || !$channelName) {
            return response()->json(['message' => 'Missing parameters'], 422);
        }

        $sellerId = Session::get('seller_id');
        if (!$sellerId) {
            return response()->json(['message' => 'Forbidden - No seller session'], 403);
        }

        // Allowed: private-seller.{sellerId}
        if ($channelName === 'private-seller.' . $sellerId) {
            return $this->signChannel($socketId, $channelName);
        }

        // Allowed: private-favor.{favorId} if favor's seller_id matches
        if (preg_match('/^private-favor\.(\d+)$/', $channelName, $m)) {
            $favor = \App\Models\Favor::find($m[1]);
            if ($favor && (int) $favor->seller_id === (int) $sellerId) {
                return $this->signChannel($socketId, $channelName);
            }
        }

        // Allowed: private-tracking.{orderId} / private-job.{orderId} if delivery order belongs to seller's store
        if (preg_match('/^private-(tracking|job)\.(\d+)$/', $channelName, $m)) {
            $orderId = $m[2];
            $order = \App\Models\DeliveryOrder::where('id', $orderId)
                ->whereHas('store', fn($q) => $q->where('seller_id', $sellerId))
                ->first();
            if ($order) {
                return $this->signChannel($socketId, $channelName);
            }
        }

        return response()->json(['message' => 'Forbidden - Channel mismatch'], 403);
    }

    private function signChannel($socketId, $channelName)
    {
        $reverbSecret = config('reverb.apps.apps.0.secret');
        $reverbKey    = config('reverb.apps.apps.0.key');
        $str          = $socketId . ':' . $channelName;
        $hash         = hash_hmac('sha256', $str, $reverbSecret);

        return response()->json([
            'auth' => $reverbKey . ':' . $hash,
        ]);
    }

    private function seller()
    {
        $seller = Seller::find(Session::get('seller_id'));
        if (!$seller) {
            Session::forget('seller_id');
            Session::forget('seller_staff_id');
            abort(redirect()->route('seller.login'));
        }
        return $seller;
    }

    private function store()
    {
        $s = $this->seller();
        return Store::where('seller_id', $s->id)->first();
    }

    private function requirePremium($feature)
    {
        $store = $this->store();
        if (!$store || !$store->is_premium) {
            if (request()->expectsJson()) {
                return response()->json(['status' => false, 'result' => 'Requiere plan Premium. Suscríbete en Paquetes Empresariales.']);
            }
            return null;
        }
        return $store;
    }

    private function requireRestaurant()
    {
        $store = $this->store();
        if (!$store || !$store->isRestaurant()) {
            abort(redirect()->route('seller.dashboard')->with('error', 'Esta función solo está disponible para restaurantes.'));
        }
        return $store;
    }

    // ── Token login bridge ──
    public static function tokenLogin(Request $request)
    {
        $token = $request->token;
        if (!$token) return redirect()->route('home');

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken || !$accessToken->tokenable instanceof Seller) {
            return redirect()->route('home');
        }
        if (!$accessToken->tokenable->status) {
            return redirect()->route('home')->with('error', 'Cuenta suspendida');
        }

        Session::forget('seller_staff_id');
        Session::put('seller_id', $accessToken->tokenable->id);
        
        $store = Store::where('seller_id', $accessToken->tokenable->id)->first();
        $defaultRoute = ($store && $store->isRestaurant()) ? route('seller.pos.floorplan') : route('seller.pos');

        return redirect($request->redirect ?? $defaultRoute);
    }

    // ── Web Login ──

    public function showLogin()
    {
        if (Session::has('seller_id')) {
            $store = $this->store();
            if ($store && $store->isRestaurant()) {
                return redirect()->route('seller.pos.floorplan');
            }
            return redirect()->route('seller.pos');
        }
        $pageTitle = 'Seller Login - Lizto POS';
        return view('seller.pos.login', compact('pageTitle'));
    }

    public function webLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:4',
        ]);

        // 1. Check main seller account
        $seller = Seller::where('email', $request->email)->first();
        if ($seller && Hash::check($request->password, $seller->password)) {
            if (!$seller->status) {
                return back()->with('error', 'Cuenta suspendida')->withInput();
            }
            Session::forget('seller_staff_id');
            Session::put('seller_id', $seller->id);
            $store = Store::where('seller_id', $seller->id)->first();
            if ($store && !$store->is_premium) {
                return redirect()->route('seller.delivery.request');
            }
            if ($store && $store->isRestaurant()) {
                return redirect()->intended(route('seller.pos.floorplan'));
            }
            return redirect()->intended(route('seller.pos'));
        }

        // 2. Check employee/staff account
        $staff = \App\Models\PosStaff::where('email', $request->email)->first();
        if ($staff && $staff->password && Hash::check($request->password, $staff->password)) {
            if ($staff->status !== 'active') {
                return back()->with('error', 'Cuenta de empleado inactiva.')->withInput();
            }
            Session::put('seller_id', $staff->seller_id);
            Session::put('seller_staff_id', $staff->id);
            if ($staff->seller_company_id) {
                Session::put('active_company_id', $staff->seller_company_id);
            }
            if ($staff->position === 'contabilidad' || $staff->hasPermission('accounting')) {
                return redirect()->route('seller.declarations');
            }
            $store = Store::where('seller_id', $staff->seller_id)->first();
            if ($store && !$store->is_premium) {
                return redirect()->route('seller.delivery.request');
            }
            if ($store && $store->isRestaurant()) {
                if ($staff->hasPermission('kitchen') && !$staff->hasPermission('pos_orders')) {
                    return redirect()->intended(route('seller.pos.kitchen'));
                }
                return redirect()->intended(route('seller.pos.floorplan'));
            }
            return redirect()->intended(route('seller.pos'));
        }

        return back()->with('error', 'Credenciales incorrectas')->withInput();
    }

    public function webLogout()
    {
        Session::forget('seller_id');
        Session::forget('seller_staff_id');
        return redirect()->route('seller.login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:sellers,email',
            'password'      => 'required|min:6',
            'phone'         => 'required|string|max:20',
            'address'       => 'required|string|max:500|not_in:-',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'zone_id'       => 'required|exists:zones,id',
            'business_name' => 'nullable|string|max:200',
            'trade_name'    => 'nullable|string|max:200',
            'ruc_number'    => 'nullable|string|max:15',
            'store_type'    => 'required|in:restaurant,supermarket,pharmacy,liquor_store,pet_shop',
        ], [
            'address.not_in' => 'Debe buscar y seleccionar una dirección del autocompletado de Google Maps',
            'latitude.required' => 'Debe seleccionar una dirección válida del autocompletado de Google Maps',
            'longitude.required' => 'Debe seleccionar una dirección válida del autocompletado de Google Maps',
        ]);

        $seller = \App\Models\Seller::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'phone'           => $request->phone,
            'address'         => $request->address,
            'zone_id'         => $request->zone_id,
            'document_type'   => 'RUC',
            'document_number' => $request->ruc_number ?: null,
            'business_name'   => $request->business_name ?: null,
            'trade_name'      => $request->trade_name ?: null,
            'status'          => 1,
        ]);

        // Capture optional register RUC
        $rucVal = $request->business_name ? '20' . getNumber(9) : null; // Default random RUC if not provided or let's inspect the request details
        if ($request->has('business_name') || $request->has('trade_name')) {
            // Let's check if the form had a RUC field. The form had: id="reg-ruc" but didn't have name="ruc"!
            // Let's update register action to check for RUC.
        }

        // Auto-create store
        $subCat = \App\Models\SubCategory::first();
        $store = \App\Models\Store::create([
            'seller_id'       => $seller->id,
            'sub_category_id' => $subCat?->id ?? 1,
            'name'            => $request->trade_name ?: $request->business_name ?: $request->name,
            'address'         => $request->address,
            'latitude'        => $request->latitude,
            'longitude'       => $request->longitude,
            'store_type'      => $request->store_type ?? 'restaurant',
            'status'          => 1,
            'is_open'         => 1,
        ]);

        // Auto-create SellerCompany for invoicing using registration data if business_name is provided
        if ($request->business_name) {
            $companyRuc = $request->input('ruc_number') ?: '20000000000';
            $sellerCompany = \App\Models\SellerCompany::create([
                'seller_id'       => $seller->id,
                'document_number' => $companyRuc,
                'business_name'   => $request->business_name,
                'trade_name'      => $request->trade_name ?: $request->business_name,
                'address'         => $request->address,
                'ubigeo'          => '150101',
            ]);
            Session::put('active_company_id', $sellerCompany->id);
        }

        Session::put('seller_id', $seller->id);
        return redirect()->route('seller.delivery.request')->with('success', '¡Bienvenido! Tu tienda ha sido creada. Puedes empezar solicitando un envío gratis.');
    }

    // ── Dashboard ──

    public function dashboard(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Dashboard';
        $storeId = $store?->id;

        // Date range filter (default: today / this month)
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $period = $request->input('period', 'month'); // today | week | month | custom

        if ($period === 'today') {
            $dateFrom = now()->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');
        } elseif ($period === 'week') {
            $dateFrom = now()->startOfWeek()->format('Y-m-d');
            $dateTo = now()->endOfWeek()->format('Y-m-d');
        } elseif ($period === 'month') {
            $dateFrom = now()->startOfMonth()->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');
        }

        // POS stats (cached 60s)
        $cacheKey = "dashboard_{$seller->id}_{$dateFrom}_{$dateTo}";
        $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($seller, $storeId, $dateFrom, $dateTo) {
            $posRange = PosOrder::where('seller_id', $seller->id)->whereBetween('created_at', [$dateFrom, now()->parse($dateTo)->endOfDay()]);
            $posToday = PosOrder::where('seller_id', $seller->id)->whereDate('created_at', today());
            $posMonth = PosOrder::where('seller_id', $seller->id)->whereMonth('created_at', now()->month);

            $delRange = DeliveryOrder::where('store_id', $storeId)->whereBetween('created_at', [$dateFrom, now()->parse($dateTo)->endOfDay()]);
            $delToday = DeliveryOrder::where('store_id', $storeId)->whereDate('created_at', today());
            $delMonth = DeliveryOrder::where('store_id', $storeId)->whereMonth('created_at', now()->month);

            return [
                'pos_today_count'     => $posToday->count(),
                'pos_today_sales'     => $posToday->sum('total'),
                'pos_month_count'     => $posMonth->count(),
                'pos_month_sales'     => $posMonth->sum('total'),
                'del_today_count'     => $delToday->count(),
                'del_today_sales'     => $delToday->sum('total'),
                'del_month_count'     => $delMonth->count(),
                'del_month_sales'     => $delMonth->sum('total'),
                'total_sales_today'   => $posToday->sum('total') + $delToday->sum('total'),
                'total_sales_month'   => $posMonth->sum('total') + $delMonth->sum('total'),
                'kitchen_pending'     => PosOrder::where('seller_id', $seller->id)->whereIn('status', ['confirmed', 'preparing'])->count(),
                'active_tables'       => PosTable::where('seller_id', $seller->id)->where('status', 'occupied')->count(),
                'receivables_amount'  => (float) $seller->receivable_balance,
                'range_sales'         => $posRange->sum('total') + $delRange->sum('total'),
                'range_orders'        => $posRange->count() + $delRange->count(),
            ];
        });

        // Chart: date range sales (for Chart.js)
        $chartDays = collect(range(29, 0))->map(function ($d) use ($seller, $storeId) {
            $date = now()->subDays($d)->format('Y-m-d');
            $pos = PosOrder::where('seller_id', $seller->id)->whereDate('created_at', $date)->sum('total');
            $del = DeliveryOrder::where('store_id', $storeId)->whereDate('created_at', $date)->sum('total');
            return ['date' => $date, 'pos' => round($pos, 2), 'del' => round($del, 2), 'total' => round($pos + $del, 2)];
        });

        // Last 10 orders combined
        $posOrders = PosOrder::where('seller_id', $seller->id)->with('table')->latest()->limit(10)->get()->map(fn($o) => [
            'source' => 'POS', 'order_no' => $o->order_no, 'customer' => $o->customer_name,
            'type' => $o->order_type, 'table' => $o->table?->name, 'total' => $o->total,
            'status' => $o->status, 'created_at' => $o->created_at,
        ]);

        $delOrders = DeliveryOrder::where('store_id', $storeId)->with('user')->latest()->limit(10)->get()->map(fn($o) => [
            'source' => 'Delivery', 'order_no' => $o->order_no, 'customer' => $o->user?->fullname,
            'type' => 'delivery', 'table' => null, 'total' => $o->total,
            'status' => $o->status, 'created_at' => $o->created_at,
        ]);

        $recent = $posOrders->concat($delOrders)->sortByDesc('created_at')->take(15)->values();

        $customers = PosOrder::where('seller_id', $seller->id)
            ->whereNotNull('customer_phone')
            ->selectRaw('customer_name, customer_phone, COUNT(*) as total_orders, SUM(total) as total_spent, MAX(created_at) as last_order')
            ->groupBy('customer_phone', 'customer_name')
            ->orderByDesc('total_orders')
            ->limit(10)->get();

        // Enhanced KPI Data
        $totalOrdersToday = $stats['pos_today_count'] + $stats['del_today_count'];
        $totalOrdersMonth = $stats['pos_month_count'] + $stats['del_month_count'];
        $stats['avg_ticket_today'] = $totalOrdersToday > 0 ? round($stats['total_sales_today'] / $totalOrdersToday, 2) : 0;
        $stats['avg_ticket_month'] = $totalOrdersMonth > 0 ? round($stats['total_sales_month'] / $totalOrdersMonth, 2) : 0;

        $lastMonthPos = PosOrder::where('seller_id', $seller->id)->whereMonth('created_at', now()->subMonth()->month)->sum('total');
        $lastMonthDel = DeliveryOrder::where('store_id', $storeId)->whereMonth('created_at', now()->subMonth()->month)->sum('total');
        $lastMonthTotal = $lastMonthPos + $lastMonthDel;
        $stats['sales_growth_pct'] = $lastMonthTotal > 0 ? round((($stats['total_sales_month'] - $lastMonthTotal) / $lastMonthTotal) * 100, 1) : 0;

        $stats['pos_pct'] = $totalOrdersMonth > 0 ? round(($stats['pos_month_count'] / $totalOrdersMonth) * 100, 0) : 0;
        $stats['del_pct'] = $totalOrdersMonth > 0 ? round(($stats['del_month_count'] / $totalOrdersMonth) * 100, 0) : 0;

        $stats['completed_today'] = PosOrder::where('seller_id', $seller->id)->whereDate('created_at', today())->where('status', 'delivered')->count()
            + DeliveryOrder::where('store_id', $storeId)->whereDate('created_at', today())->where('status', 'delivered')->count();

        // Hourly distribution today
        $hourlyOrders = PosOrder::where('seller_id', $seller->id)->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total) as sales')
            ->groupBy('hour')->get()->keyBy('hour');

        $hourlyDelivery = DeliveryOrder::where('store_id', $storeId)->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total) as sales')
            ->groupBy('hour')->get()->keyBy('hour');

        $hourlyChart = collect(range(0, 23))->map(function ($h) use ($hourlyOrders, $hourlyDelivery) {
            $pos = $hourlyOrders->get($h);
            $del = $hourlyDelivery->get($h);
            return [
                'hour' => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
                'pos' => $pos ? $pos->count : 0,
                'del' => $del ? $del->count : 0,
                'total' => ($pos ? $pos->count : 0) + ($del ? $del->count : 0),
            ];
        });

        $statusBreakdown = PosOrder::where('seller_id', $seller->id)->whereMonth('created_at', now()->month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->get();

        $topProducts = PosOrderItem::whereHas('order', fn($q) => $q->where('seller_id', $seller->id)->whereMonth('created_at', now()->month))
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(total_price) as total_revenue')
            ->groupBy('product_name')->orderByDesc('total_qty')->limit(10)->get();

        $heatmapPoints = PosOrder::where('seller_id', $seller->id)
            ->whereNotNull('delivery_lat')->whereNotNull('delivery_lng')
            ->where('created_at', '>=', now()->subDays(60))
            ->where('order_type', 'delivery')
            ->selectRaw('delivery_lat as lat, delivery_lng as lng, COUNT(*) as weight')
            ->groupBy('delivery_lat', 'delivery_lng')
            ->get();

        $ordersByDay = PosOrder::where('seller_id', $seller->id)->whereMonth('created_at', now()->month)
            ->selectRaw('DAYNAME(created_at) as day_name, DAYOFWEEK(created_at) as day_num, COUNT(*) as count')
            ->groupBy('day_name', 'day_num')->orderBy('day_num')->get();

        return view('seller.dashboard', compact(
            'pageTitle', 'seller', 'store', 'stats', 'chartDays', 'recent', 'customers',
            'hourlyChart', 'statusBreakdown', 'topProducts', 'heatmapPoints', 'ordersByDay',
            'dateFrom', 'dateTo', 'period'
        ));
    }

    // ── POS ──

    public function pos()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'POS - ' . ($store?->name ?? 'Punto de Venta');

        $tables = PosTable::where('seller_id', $seller->id)->orderBy('area')->orderBy('sort_order')->get();
        $activeOrders = PosOrder::where('seller_id', $seller->id)
            ->where('payment_status', 'pending')
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('staff')
            ->latest()
            ->get()
            ->groupBy('pos_table_id');

        foreach ($tables as $table) {
            $targetId = $table->linked_to_table_id ?: $table->id;
            $table->active_order = isset($activeOrders[$targetId]) ? $activeOrders[$targetId]->first() : null;
        }
        $areas = PosArea::where('seller_id', $seller->id)->orderBy('sort_order')->get();
        $products = Product::where('store_id', $store?->id)->with('variations', 'addons')->active()->orderBy('store_category_id')->orderBy('sort_order')->get();
        $categories = $store ? $store->categories()->orderBy('sort_order')->get() : collect();

        $productsJson = '{}';
        if ($products->isNotEmpty()) {
            $productsJson = $products->keyBy('id')->map(function($p) {
                return [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'price'       => $p->finalPrice(),
                    'barcode'     => $p->barcode,
                    'variations'  => $p->variations->map(function($v) { return ['id' => $v->id, 'name' => $v->name, 'price' => (float) $v->price]; })->values(),
                    'addons'      => $p->addons->map(function($a) { return ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price]; })->values(),
                ];
            })->toJson();
        }

        // Pending orders ready for payment
        $pendingPayment = PosOrder::where('seller_id', $seller->id)
            ->whereIn('status', ['delivered', 'ready'])
            ->where('payment_status', 'pending')
            ->with('items', 'table')
            ->latest()->limit(10)->get();

        $isCashOpen = PosCashSession::where('seller_id', $seller->id)->open()->exists();
        $invoiceTypes = PosInvoiceType::where('seller_id', $seller->id)->with('series')->get();
        $staff = \App\Models\PosStaff::where('seller_id', $seller->id)->where('status', 'active')->orderBy('name')->get();

        $bankAccounts = PosBankAccount::where('seller_id', $seller->id)->active()->get();
        return view('seller.pos.index', compact('pageTitle', 'seller', 'store', 'tables', 'areas', 'products', 'categories', 'productsJson', 'pendingPayment', 'invoiceTypes', 'isCashOpen', 'staff', 'bankAccounts'));
    }

    // ── Tables ──

    public function tables()
    {
        $this->requireRestaurant();
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Gestión de Mesas';
        $tables = PosTable::where('seller_id', $seller->id)->orderBy('pos_area_id')->orderBy('sort_order')->get();
        return view('seller.pos.tables', compact('pageTitle', 'seller', 'store', 'tables'));
    }

    public function tableSave(Request $request)
    {
        $this->requireRestaurant();
        $request->validate(['name' => 'required|string|max:50', 'capacity' => 'integer|min:1']);
        $seller = $this->seller();
        PosTable::create([
            'seller_id'   => $seller->id,
            'store_id'    => $this->store()?->id,
            'name'        => $request->name,
            'pos_area_id' => $request->pos_area_id ?: null,
            'area'        => $request->area ?: null,
            'capacity'    => $request->capacity ?? 4,
            'sort_order'  => $request->sort_order ?? 0,
            'status'      => 'free',
        ]);
        return back()->with('success', 'Mesa creada');
    }

    public function tableUpdate(Request $request, $id)
    {
        $table = PosTable::where('seller_id', $this->seller()->id)->findOrFail($id);
        $table->update([
            'name'        => $request->name,
            'pos_area_id' => $request->pos_area_id ?: null,
            'area'        => $request->area ?: null,
            'capacity'    => $request->capacity ?? 4,
            'sort_order'  => $request->sort_order ?? 0,
        ]);
        return back()->with('success', 'Mesa actualizada');
    }

    public function tableDelete($id)
    {
        $this->requireRestaurant();
        PosTable::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Mesa eliminada');
    }

    public function tableStatus($id)
    {
        $this->requireRestaurant();
        $table = PosTable::where('seller_id', $this->seller()->id)->findOrFail($id);
        $table->update(['status' => $table->status === 'free' ? 'occupied' : 'free']);
        return back();
    }

    public function areaStore(Request $request)
    {
        $this->requireRestaurant();
        $request->validate(['name' => 'required|string|max:50']);
        PosArea::create(['seller_id' => $this->seller()->id, 'name' => $request->name]);
        return back()->with('success', 'Área "' . $request->name . '" creada');
    }

    public function areaDelete(Request $request)
    {
        $this->requireRestaurant();
        $area = PosArea::where('seller_id', $this->seller()->id)->findOrFail($request->id);
        PosTable::where('pos_area_id', $area->id)->update(['pos_area_id' => null]);
        $area->delete();
        return back()->with('success', 'Área eliminada');
    }

    public function tablePosition(Request $request)
    {
        $this->requireRestaurant();
        $table = PosTable::where('seller_id', $this->seller()->id)->findOrFail($request->id);
        $table->update(['pos_x' => (int) $request->x, 'pos_y' => (int) $request->y]);
        return response()->json(['ok' => true]);
    }

    public function floorPlan()
    {
        $this->requireRestaurant();
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Salón y Mesas';
        $areas = PosArea::where('seller_id', $seller->id)->with('tables')->orderBy('sort_order')->get();
        $reservations = \App\Models\PosTableReservation::where('seller_id', $seller->id)
            ->whereDate('reservation_time', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();
        return view('seller.pos.floorplan', compact('pageTitle', 'seller', 'store', 'areas', 'reservations'));
    }

    public function floorPlanUpload(Request $request)
    {
        $this->requireRestaurant();
        $seller = $this->seller();

        $request->validate([
            'area_id' => 'required|integer',
            'image'   => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $area = PosArea::where('seller_id', $seller->id)->findOrFail($request->area_id);

        if ($request->hasFile('image')) {
            try {
                $oldImage = public_path('assets/images/areas/' . $area->floorplan_image);
                if ($area->floorplan_image && file_exists($oldImage)) {
                    @unlink($oldImage);
                }

                $file = $request->file('image');
                $filename = 'area_' . $area->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('assets/images/areas');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $filename);
                $area->update(['floorplan_image' => $filename]);

                return back()->with('success', 'Plano personalizado subido correctamente.');
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir archivo: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'No se ha seleccionado ninguna imagen.');
    }

    // ── Billing & Invoicing ──


    public function billing()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Cobrar y Facturar';
        $pendingOrders = PosOrder::where('seller_id', $seller->id)
            ->whereIn('status', ['delivered', 'ready'])
            ->whereIn('payment_status', ['pending', 'credit'])
            ->with('items', 'table')
            ->latest()->get();

        $paidOrders = PosOrder::where('seller_id', $seller->id)
            ->where(function($q) {
                $q->whereIn('payment_status', ['paid', 'credit'])
                  ->orWhereNotNull('invoice_series');
            })
            ->with('items', 'table', 'sunatInvoice')
            ->latest()->paginate(15);

        $invoiceTypes = PosInvoiceType::where('seller_id', $seller->id)->with('series')->get();
        $bankAccounts = PosBankAccount::where('seller_id', $seller->id)->active()->get();

        return view('seller.pos.billing', compact('pageTitle', 'seller', 'store', 'pendingOrders', 'paidOrders', 'invoiceTypes', 'bankAccounts'));
    }

    public function payOrder(Request $request, $id)
    {
        $seller = $this->seller();
        $isCashOpen = PosCashSession::where('seller_id', $seller->id)->open()->exists();
        if (!$isCashOpen) {
            $msg = 'Debe abrir una caja antes de procesar el pago de un pedido.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $order = PosOrder::where('seller_id', $seller->id)->with('items')->find($id);
        if (!$order) {
            $msg = 'El pedido especificado no existe o fue eliminado.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $msg], 404);
            }
            return back()->with('error', $msg);
        }

        $request->validate([
            'series_id'        => 'nullable|integer',
            'tipo_doc'         => 'nullable|string|in:1,6',
            'num_doc'          => 'nullable|string|max:20',
            'customer_name'    => 'nullable|string|max:255',
            'payment_method'   => 'nullable|string',
            'payments'         => 'nullable|array',
            'payment_accounts' => 'nullable|array',
            'detail_mode'      => 'nullable|in:detailed,consumption',
            'consumption_description' => 'nullable|string|max:250|required_if:detail_mode,consumption',
            'include_tupper'   => 'nullable|boolean',
        ]);

        // The fee is validated first and persisted only once the payment is
        // valid, so a rejected payment never leaves an orphaned Tupper line.
        $includeTupper = $request->boolean('include_tupper');
        $hasTupper = $order->items()
            ->where('notes', 'Cargo automático de tupper (S/ 1.00)')
            ->exists();
        $tupperFee = $includeTupper && !$hasTupper ? 1.00 : 0.00;

        // Keep the selected presentation mode intact through the whole payment
        // flow.  This is later used both for the local invoice and the SUNAT
        // payload, so a consumption sale must never fall back to item detail.
        $detailMode = $request->input('detail_mode') === 'consumption' ? 'consumption' : 'detailed';
        $consumptionDescription = $detailMode === 'consumption'
            ? trim((string) $request->input('consumption_description', ''))
            : null;
        if ($detailMode === 'consumption' && $consumptionDescription === '') {
            $consumptionDescription = 'Consumo';
        }

        $paymentMethod = $request->payment_method ?? 'cash';
        $paymentDetails = null;

        if ($request->has('payments') && is_array($request->payments)) {
            $splits = [];
            foreach ($request->payments as $method => $amount) {
                if ($amount > 0) {
                    $splits[$method] = round((float) $amount, 2);
                }
            }
            if (count($splits) > 0) {
                $splitTotal = round(array_sum($splits), 2);
                $orderTotal = round((float) $order->total + $tupperFee, 2);
                if (abs($splitTotal - $orderTotal) > 0.01) {
                    $message = 'El monto total ingresado debe coincidir con el total de la cuenta.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['status' => false, 'message' => $message], 422);
                    }
                    return back()->with('error', $message)->withInput();
                }
                $paymentDetails = $splits;
                if (count($splits) > 1) {
                    $paymentMethod = 'split';
                } else {
                    $paymentMethod = array_key_first($splits);
                }
            }
        }

        $paymentStatus = 'paid';
        if ($paymentMethod === 'credit' || (is_array($paymentDetails) && isset($paymentDetails['credit']))) {
            $paymentStatus = 'credit';
        }

        // Tupper is an actual order line, not a transient payment adjustment.
        // This keeps the total, invoice/ticket detail and the audit trail in
        // pos_order_items consistent with each other.
        if ($tupperFee > 0) {
            \App\Models\PosOrderItem::create([
                'pos_order_id' => $order->id,
                'product_name' => 'Tupper',
                'quantity'     => 1,
                'unit_price'   => 1.00,
                'total_price'  => 1.00,
                'notes'        => 'Cargo automático de tupper (S/ 1.00)',
                'status'       => 'completed',
                'tax_type'     => $this->activeCompany()?->default_tax_type ?? 'gravado',
            ]);
            $order->update([
                'subtotal' => (float) $order->subtotal + 1,
                'total'    => (float) $order->total + 1,
            ]);
            $order->refresh()->load('items');
        }

        $orderData = [
            'payment_status'  => $paymentStatus,
            'paid_at'         => $paymentStatus === 'paid' ? now() : null,
            'payment_method'  => $paymentMethod,
            'payment_details' => $paymentDetails,
        ];
        if ($request->customer_name) {
            $orderData['customer_name'] = $request->customer_name;
        }
        if ($request->num_doc) {
            $orderData['customer_doc'] = $request->num_doc;
            $orderData['customer_doc_type'] = $request->tipo_doc;
        }
        $order->update($orderData);

        if ($order->pos_table_id) {
            PosTable::where('id', $order->pos_table_id)
                ->orWhere('linked_to_table_id', $order->pos_table_id)
                ->update([
                    'status' => 'free',
                    'linked_to_table_id' => null
                ]);
        }

        // Recreate transaction records for payment splits
        PosTransaction::where('pos_order_id', $order->id)->delete();
        $cashSession = PosCashSession::where('seller_id', $order->seller_id)->open()->first();

        if ($request->has('payments') && is_array($request->payments)) {
            foreach ($request->payments as $method => $amount) {
                $amount = (float) $amount;
                if ($amount > 0 && $method !== 'credit') {
                    $bankAccountId = null;
                    if ($method !== 'cash' && $request->has('payment_accounts') && isset($request->payment_accounts[$method])) {
                        $bankAccountId = (int) $request->payment_accounts[$method];
                    }

                    PosTransaction::create([
                        'cash_session_id'     => $cashSession?->id,
                        'seller_id'           => $order->seller_id,
                        'pos_order_id'        => $order->id,
                        'type'                => 'sale',
                        'amount'              => $amount,
                        'description'         => 'Cobro Venta #' . $order->order_no . ' (' . ucfirst($method) . ')',
                        'payment_method'      => $method,
                        'pos_bank_account_id' => $bankAccountId,
                    ]);
                }
            }
        } else {
            PosTransaction::create([
                'cash_session_id'     => $cashSession?->id,
                'seller_id'           => $order->seller_id,
                'pos_order_id'        => $order->id,
                'type'                => 'sale',
                'amount'              => $order->total,
                'description'         => 'Cobro Venta #' . $order->order_no,
                'payment_method'      => $paymentMethod,
                'pos_bank_account_id' => null,
            ]);
        }

        $this->consumeStockForOrder($order->seller_id, $order->items);

        $store = $this->store();
        if ($store) {
            $store->dispatchWebhook('order.paid', [
                'id'             => $order->id,
                'order_no'       => $order->order_no,
                'payment_method' => $order->payment_method,
                'total'          => (float) $order->total,
                'paid_at'        => $order->paid_at ? $order->paid_at->toIso8601String() : now()->toIso8601String(),
            ]);
        }

        $message = 'Pedido #' . $order->order_no . ' cobrado';

        $seriesId = $request->series_id;
        if (!$seriesId) {
            $activeCompany = $this->activeCompany();
            if ($activeCompany) {
                $nvSeries = PosInvoiceSeries::where('seller_company_id', $activeCompany->id)
                    ->whereHas('invoiceType', function($q) {
                        $q->where('code', 'NV');
                    })->first();
                if ($nvSeries) {
                    $seriesId = $nvSeries->id;
                }
            }
        }

        if ($seriesId) {
            $activeCompany = $this->activeCompany();
            if (!$activeCompany) {
                $msg = 'Debes configurar una empresa antes de emitir comprobantes.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            $series = PosInvoiceSeries::where('id', $seriesId)
                ->where('seller_company_id', $activeCompany->id)
                ->with('invoiceType')
                ->first();

            if (!$series) {
                $msg = 'La serie de comprobante seleccionada no es válida.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

            if ($tipoDoc !== 'NV' && $store && $store->hasReachedInvoiceLimit()) {
                $planName = $store->activePackagesRelation()->first()?->package?->name ?? 'Actual';
                $limit = $store->getInvoiceLimit() ?? 50;
                $msg = "Has alcanzado el límite de {$limit} comprobantes electrónicos de tu plan {$planName}. Actualiza a un plan superior para facturación ilimitada.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => false, 'message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            $correlativo = \Illuminate\Support\Facades\DB::transaction(function () use ($series) {
                $locked = PosInvoiceSeries::where('id', $series->id)->lockForUpdate()->first();
                $num = $locked->current_number;
                $locked->increment('current_number');
                return $num;
            });
            $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

            $clientData = [
                'tipo_doc' => $request->tipo_doc ?? ($tipoDoc === '01' ? '6' : '1'),
                'num_doc'  => $request->num_doc ?? ($order->customer_doc ?? '0'),
                'nombre'   => $request->customer_name ?? $order->customer_name ?? 'CLIENTE VARIOS',
            ];

            $isElectronic = $series->invoiceType->is_electronic ?? false;
            $isNV = ($tipoDoc === 'NV');

            // Calcular los totales de impuestos sumando cada ítem según su régimen tributario (tax_type)
            $totalGravada   = 0;
            $totalExonerada = 0;
            $totalInafecta  = 0;
            $totalIgv       = 0;

            foreach ($order->items as $item) {
                if ($detailMode === 'consumption') {
                    $itemTaxType = $activeCompany->default_tax_type ?? 'gravado';
                } else {
                    $itemTaxType = $item->tax_type ?? ($item->product?->tax_type ?? 'gravado');
                }
                $lineTotal   = (float) $item->total_price;

                if ($itemTaxType === 'exonerado') {
                    $totalExonerada += $lineTotal;
                } elseif ($itemTaxType === 'inafecto') {
                    $totalInafecta += $lineTotal;
                } else { // gravado
                    $base = round($lineTotal / 1.18, 2);
                    $totalGravada += $base;
                    $totalIgv += round($lineTotal - $base, 2);
                }
            }

            // Para Nota de Venta (NV) u otros documentos locales no electrónicos, todo se emite sin impuestos
            if ($isNV || !$isElectronic) {
                $totalGravada   = 0;
                $totalExonerada = 0;
                $totalInafecta  = 0;
                $totalIgv       = 0;
            }

            // Estado SUNAT: NV y no-electrónicos -> "Generado" (azul), electrónicos -> "Pendiente"
            $cdrStatus = ($isNV || !$isElectronic)
                ? \App\Models\SunatInvoice::STATUS_GENERATED
                : \App\Models\SunatInvoice::STATUS_PENDING;

            \App\Models\SunatInvoice::create([
                'seller_id'        => $this->seller()->id,
                'seller_company_id'=> $activeCompany->id,
                'pos_order_id'     => $order->id,
                'tipo_doc'         => $tipoDoc,
                'serie'            => $series->series,
                'correlativo'      => $correlativo,
                'cliente_tipo_doc' => $clientData['tipo_doc'],
                'cliente_num_doc'  => $clientData['num_doc'],
                'cliente_nombre'   => $clientData['nombre'],
                'detail_mode'      => $detailMode,
                'consumption_description' => $consumptionDescription,
                'total_gravada'    => $totalGravada,
                'total_exonerada'  => $totalExonerada,
                'total_inafecta'   => $totalInafecta,
                'total_igv'        => $totalIgv,
                'total'            => $order->total,
                'moneda'           => 'PEN',
                'cdr_status'       => $cdrStatus,
                'cdr_response'     => ($isNV || !$isElectronic) ? json_encode(['code' => '0', 'desc' => 'Comprobante Emitido Localmente']) : null,
                'sunat_response'   => ($isNV || !$isElectronic) ? 'OK' : 'PENDING',
                'fecha_emision'    => now(),
            ]);

            $number = str_pad($correlativo, 8, '0', STR_PAD_LEFT);
            $order->update([
                'invoice_type_id'   => $series->invoiceType->id,
                'invoice_series'    => $series->series,
                'invoice_number'    => $number,
                'invoice_type_code' => $tipoDoc,
                'customer_doc'      => $clientData['num_doc'],
                'customer_doc_type' => $clientData['tipo_doc'],
            ]);

            if (!$isElectronic || $isNV) {
                $message .= ' - ' . $series->series . '-' . $number . ' emitido con éxito';
            } else {
                $message .= ' - ' . $series->series . '-' . $number . ' pendiente de envío a SUNAT';
            }
        }

        if ($request->expectsJson()) {
            $invoiceId = $order->sunatInvoice?->id;
            return response()->json([
                'status' => true,
                'message' => $message,
                'order_id' => $order->id,
                'invoice_id' => $invoiceId,
                'has_invoice' => !empty($invoiceId),
            ]);
        }

        return back()->with('success', $message);
    }

    public function preCheckTicket($id)
    {
        $order = PosOrder::where('seller_id', $this->seller()->id)->with('items', 'table', 'store')->findOrFail($id);
        $store = $order->store;

        $logoBase64 = null;
        if ($store && $store->image) {
            $logoPath = public_path('assets/images/store/' . $store->image);
            if (!file_exists($logoPath)) {
                $logoPath = base_path('../assets/images/store/' . $store->image);
            }
            if (file_exists($logoPath)) {
                try {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
        }

        return view('seller.pos.ticket', compact('order', 'logoBase64'));
    }

    public function saveDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'type'  => 'required|in:web,android,ios',
        ]);

        $seller = $this->seller();
        $posStaffId = session()->get('pos_staff_id');

        \App\Models\DeviceToken::updateOrCreate(
            [
                'token' => $request->token,
            ],
            [
                'seller_id'    => $seller->id,
                'pos_staff_id' => $posStaffId,
                'type'         => $request->type,
                'active'       => true,
            ]
        );

        return response()->json(['status' => true, 'message' => 'Token web guardado con éxito']);
    }

    // ── Mass Notifications ──

    public function notifications()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Notificaciones Push';
        $userCount = \App\Models\User::where('status', 1)->count();
        return view('seller.notifications', compact('pageTitle', 'seller', 'store', 'userCount'));
    }

    public function sendNotification(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        $request->validate(['title' => 'required|string|max:100', 'body' => 'required|string|max:255']);

        $users = \App\Models\User::where('status', 1)->get();
        $count = 0;
        foreach ($users as $user) {
            \App\Services\FcmService::sendToUser($user, $request->title, $request->body, [
                'store_id' => (string) ($store?->id ?? ''),
                'store_name' => $store?->name ?? '',
                'type' => 'seller_promotion',
            ]);
            $count++;
        }

        return back()->with('success', 'Notificación enviada a ' . $count . ' usuarios');
    }

    // ── QR Menu Printable ──

    public function qrMenu()
    {
        $this->requireRestaurant();
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'QR de Carta';
        $storeUrl = route('delivery.store', $store ?? 1);
        $options = new \chillerlan\QRCode\QROptions([
            'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
            'outputBase64'    => true,
            'scale'           => 8
        ]);
        try {
            $qrCode = new \chillerlan\QRCode\QRCode($options);
            $qrUrl = $qrCode->render($storeUrl);
        } catch (\Throwable $e) {
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&color=16a34a&data=' . urlencode($storeUrl);
        }
        return view('seller.qrmenu', compact('pageTitle', 'seller', 'store', 'storeUrl', 'qrUrl'));
    }

    // ── Quick External Order Entry ──

    public function externalOrder(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Pedido Externo';

        if ($request->isMethod('POST')) {
            $request->merge(['items' => json_encode($request->input('items', []))]);
            return $this->orderCreate($request);
        }

        $products = \App\Models\Product::where('store_id', $store?->id)->where('status', 1)->get();
        $categories = \App\Models\StoreCategory::where('store_id', $store?->id)->orderBy('sort_order')->get();
        return view('seller.external_order', compact('pageTitle', 'seller', 'store', 'products', 'categories'));
    }

    // ── Pricing ──

    public function pricing()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Planes y Precios';
        $packages = \App\Models\BusinessPackage::active()->orderBy('sort_order')->get();
        $activePackageTypes = $store?->storePackages->filter(fn($sp) => $sp->isActive())->pluck('package.type')->toArray() ?? [];
        $mercadoPagoCurrency = \App\Models\GatewayCurrency::where('gateway_alias', 'MercadoPago')->orderByDesc('id')->first();
        $payments = \App\Models\StorePackagePayment::where('seller_id', $seller->id)->where('status', '!=', 'initiated')->with('package')->latest()->get();
        return view('seller.pricing', compact('pageTitle', 'seller', 'store', 'packages', 'activePackageTypes', 'mercadoPagoCurrency', 'payments'));
    }

    public function pricingCheckout(Request $request)
    {
        $seller = $this->seller();
        $store  = $this->store();

        if (!$store) {
            return response()->json(['error' => 'Primero debes tener una tienda creada para cambiar de plan.'], 422);
        }

        $request->validate([
            'package_id' => 'required|integer|exists:business_packages,id',
        ]);

        $package = \App\Models\BusinessPackage::active()->findOrFail($request->package_id);

        if ($package->type === 'free' || $package->price <= 0) {
            return response()->json(['error' => 'Este plan no requiere checkout.'], 422);
        }

        $gateway  = \App\Models\Gateway::where('alias', 'MercadoPago')->active()->first();
        $currency = \App\Models\GatewayCurrency::where('gateway_alias', 'MercadoPago')->orderByDesc('id')->first();

        if (!$gateway || !$currency) {
            return response()->json(['error' => 'MercadoPago no está configurado para recibir pagos.'], 422);
        }

        $gatewayParams = json_decode($gateway->gateway_parameters);
        $accessToken   = $gatewayParams->access_token->value ?? ($gatewayParams->access_token ?? null);
        $publicKey     = $gatewayParams->public_key->value   ?? ($gatewayParams->public_key   ?? null);

        if (!$accessToken || $accessToken === '--------------') {
            return response()->json(['error' => 'Falta configurar el Access Token de MercadoPago.'], 422);
        }
        if (!$publicKey || $publicKey === '--------------') {
            return response()->json(['error' => 'Falta configurar la Public Key de MercadoPago.'], 422);
        }

        $packageAmount = round((float) $package->price, 2);
        $percentCharge = max(0, (float) $currency->percent_charge);
        $fixedCharge   = max(0, (float) $currency->fixed_charge);
        $totalAmount   = $percentCharge >= 100
            ? $packageAmount + $fixedCharge
            : round(($packageAmount + $fixedCharge) / (1 - ($percentCharge / 100)), 2);
        $gatewayFee    = round($totalAmount - $packageAmount, 2);
        $trx           = getTrx();

        $payment = StorePackagePayment::create([
            'store_id'         => $store->id,
            'seller_id'        => $seller->id,
            'package_id'       => $package->id,
            'trx'              => $trx,
            'gateway_alias'    => 'MercadoPago',
            'gateway_currency' => $currency->currency,
            'package_amount'   => $packageAmount,
            'gateway_fee'      => $gatewayFee,
            'total_amount'     => $packageAmount, // charge only plan price (no fee shown to user)
            'status'           => 'initiated',
        ]);

        return response()->json([
            'ok'             => true,
            'public_key'     => $publicKey,
            'trx'            => $trx,
            'payment_id'     => $payment->id,
            'amount'         => $packageAmount,
            'currency'       => $currency->currency,
            'package_name'   => $package->name,
            'payer_email'    => $seller->email,
            'payer_name'     => $seller->name,
            'process_url'    => route('seller.pricing.process'),
        ]);
    }

    public function pricingProcess(Request $request)
    {
        $seller = $this->seller();
        $store  = $this->store();

        $request->validate([
            'trx'             => 'required|string',
            'card_token'      => 'required|string',
            'installments'    => 'required|integer|min:1',
            'payment_method'  => 'required|string',
            'issuer_id'       => 'nullable',
            'payer_email'     => 'required|email',
            'payer_doc_type'  => 'nullable|string',
            'payer_doc_num'   => 'nullable|string',
        ]);

        $payment = StorePackagePayment::where('seller_id', $seller->id)
            ->where('trx', $request->trx)
            ->where('status', 'initiated')
            ->firstOrFail();

        $package = \App\Models\BusinessPackage::findOrFail($payment->package_id);

        $gateway       = \App\Models\Gateway::where('alias', 'MercadoPago')->active()->first();
        $gatewayParams = json_decode($gateway->gateway_parameters);
        $accessToken   = $gatewayParams->access_token->value ?? ($gatewayParams->access_token ?? null);

        $paymentData = [
            'transaction_amount' => (float) $payment->total_amount,
            'token'              => $request->card_token,
            'description'        => 'Plan ' . $package->name . ' - Lizto',
            'installments'       => (int) $request->installments,
            'payment_method_id'  => $request->payment_method,
            'issuer_id'          => $request->issuer_id ?: null,
            'payer'              => [
                'email'          => $request->payer_email,
                'identification' => [
                    'type'   => $request->payer_doc_type ?: 'DNI',
                    'number' => $request->payer_doc_num  ?: '',
                ],
            ],
            'external_reference'  => $payment->trx,
            'notification_url'    => route('ipn.seller.plan.mercadopago'),
            'metadata'            => [
                'trx'      => $payment->trx,
                'store_id' => $store->id,
            ],
        ];

        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($paymentData),
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'X-Idempotency-Key: ' . $payment->trx,
            ],
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $payment->update(['status' => 'failed', 'payload' => ['curl_error' => $curlError]]);
            return response()->json(['error' => 'Error de conexión con MercadoPago. Intenta de nuevo.'], 500);
        }

        $result = json_decode($response, true);
        $mpStatus = $result['status'] ?? null;

        $payment->update([
            'payload'   => $result,
            'mp_payment_id' => $result['id'] ?? null,
        ]);

        if ($mpStatus === 'approved') {
            $payment->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            // Activate the package for the store
            $package = \App\Models\BusinessPackage::find($payment->package_id);
            if ($package && $store) {
                $expiresAt = $package->duration_days > 0 ? now()->addDays($package->duration_days) : null;
                $store->storePackages()->where('status', 'active')->update(['status' => 'inactive']);
                $store->storePackages()->create([
                    'package_id' => $package->id,
                    'status'     => 'active',
                    'started_at' => now(),
                    'expires_at' => $expiresAt,
                ]);
            }

            return response()->json([
                'ok'      => true,
                'status'  => 'approved',
                'message' => '¡Pago aprobado! Tu plan ' . $package->name . ' fue activado correctamente.',
            ]);
        }

        if ($mpStatus === 'in_process' || $mpStatus === 'pending') {
            $payment->update(['status' => 'pending']);
            return response()->json([
                'ok'      => true,
                'status'  => 'pending',
                'message' => 'Tu pago está en revisión. MercadoPago te notificará la confirmación.',
            ]);
        }

        // rejected / other
        $detail = $result['status_detail'] ?? ($result['message'] ?? 'Pago rechazado.');
        $payment->update(['status' => 'failed']);
        return response()->json([
            'error'   => true,
            'status'  => $mpStatus,
            'message' => 'Pago rechazado: ' . $detail,
        ], 422);
    }

    public function pricingReturn($trx)
    {
        $seller  = $this->seller();
        $payment = StorePackagePayment::where('seller_id', $seller->id)->where('trx', $trx)->firstOrFail();

        if ($payment->status === 'paid') {
            return redirect()->route('seller.pricing')->with('success', 'Pago confirmado. Tu plan fue activado correctamente.');
        }

        return redirect()->route('seller.pricing')->with('info', 'Pago recibido en revisión. MercadoPago confirmará la operación en unos instantes.');
    }


    public function apiSettings()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Configuración API';
        return view('seller.api_settings', compact('pageTitle', 'seller', 'store'));
    }

    public function regenerateApiToken()
    {
        $store = $this->store();
        $store->update(['api_token' => \Illuminate\Support\Str::random(60)]);
        return back()->with('success', 'Token API regenerado correctamente.');
    }

    public function saveWebhookSettings(Request $request)
    {
        $request->validate([
            'webhook_url' => 'nullable|url|max:500',
        ]);

        $store = $this->store();
        $secret = $store->webhook_secret;

        if ($request->filled('webhook_url') && !$secret) {
            $secret = 'whsec_' . \Illuminate\Support\Str::random(32);
        }

        $store->update([
            'webhook_url'    => $request->webhook_url,
            'webhook_secret' => $request->filled('webhook_url') ? $secret : null,
        ]);

        return back()->with('success', 'Configuración de Webhook actualizada correctamente.');
    }

    public function saveQrSettings(Request $request)
    {
        $data = $request->validate([
            'yape_qr_string' => 'nullable|string|max:4096',
            'plin_qr_string' => 'nullable|string|max:4096',
        ]);

        $store = $this->store();
        $store->update([
            'yape_qr_string' => filled($data['yape_qr_string'] ?? null) ? trim($data['yape_qr_string']) : null,
            'plin_qr_string' => filled($data['plin_qr_string'] ?? null) ? trim($data['plin_qr_string']) : null,
        ]);

        return back()->with('success', 'Códigos QR de pago guardados correctamente.');
    }

    public function testWebhook()
    {
        $store = $this->store();
        if (!$store->webhook_url) {
            return back()->with('error', 'Debes configurar una URL de Webhook primero.');
        }

        try {
            $payload = [
                'event'     => 'test',
                'timestamp' => now()->toIso8601String(),
                'store_id'  => $store->id,
                'message'   => 'Lizto Webhook Test Successful!',
            ];

            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $store->webhook_secret);

            $ch = curl_init($store->webhook_url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-LizToGo-Signature: ' . $signature,
                    'User-Agent: LizToGo-Webhook-Dispatcher/1.0',
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return back()->with('error', 'Error al enviar webhook: ' . $error);
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return back()->with('success', 'Webhook de prueba enviado correctamente. Respuesta HTTP ' . $httpCode);
            } else {
                return back()->with('warning', 'Webhook enviado pero el servidor de destino respondió con código HTTP ' . $httpCode);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Falló el envío de prueba: ' . $e->getMessage());
        }
    }

    // ── Orders ──

    public function orderCreate(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();

        // No se requiere caja abierta para enviar comandas/registrar pedidos pendientes.
        // La validacion se realizara obligatoriamente al momento del cobro (payOrder).

        $request->validate([
            'order_id'        => 'nullable|exists:pos_orders,id',
            'pos_staff_id'    => 'nullable|exists:pos_staff,id',
            'table_id'        => 'nullable|exists:pos_tables,id',
            'order_type'      => 'required|in:dine_in,takeaway,delivery,rappi,pedidosya,llama,daz,lizto_delivery,courtesy',
            'customer_name'   => 'nullable|string|max:100',
            'customer_phone'  => 'nullable|string|max:20',
            'customer_doc_type' => 'nullable|string|in:1,6',
            'customer_doc'    => 'nullable|string|max:20',
            'delivery_address'=> 'nullable|string|max:500',
            'delivery_lat'    => 'nullable|numeric',
            'delivery_lng'    => 'nullable|numeric',
            'notes'           => 'nullable|string|max:500',
            'kitchen_notes'   => 'nullable|string|max:500',
            'courtesy'        => 'nullable|boolean',
            'discount_type'   => 'nullable|string|in:percent,fixed',
            'discount_value'  => 'nullable|numeric|min:0',
            'items'           => 'required|json',
        ]);

        $isCourtesy = filter_var($request->courtesy ?? false, FILTER_VALIDATE_BOOLEAN) || $request->order_type === 'courtesy';

        $items = json_decode($request->items, true);
        if (!is_array($items) || count($items) === 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Agrega al menos un producto'], 422);
            }
            return back()->with('error', 'Agrega al menos un producto');
        }

        $subtotal = 0;
        $orderItems = [];
        $stockErrors = [];
        foreach ($items as $item) {
            $product = Product::find($item['product_id'] ?? 0);
            $name = $item['name'] ?? ($product?->name ?? 'Producto');
            $qty = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
            $itemCourtesy = filter_var($item['is_courtesy'] ?? false, FILTER_VALIDATE_BOOLEAN) || $isCourtesy;
            $price = $itemCourtesy ? 0 : (float) ($item['price'] ?? $product?->finalPrice() ?? 0);
            $total = $price * $qty;
            $subtotal += $total;

            $hasTupper = filter_var($item['has_tupper'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $productName = $name;
            if ($hasTupper && strpos($productName, '(Con Tupper)') === false) {
                $productName .= ' (Con Tupper)';
            }

            $orderItems[] = [
                'product_id'   => $product?->id,
                'product_name' => $productName,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'total_price'  => $total,
                'tax_type'     => $product?->tax_type ?? 'gravado',
                'notes'        => $item['notes'] ?? null,
                'is_takeaway'  => filter_var($item['is_takeaway'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'status'       => 'pending',
            ];
        }

        // Validate stock for products with stock tracking (skip courtesy orders)
        if (!$isCourtesy) {
            foreach ($orderItems as $oi) {
                $product = Product::find($oi['product_id'] ?? 0);
                if (!$product || !$product->hasStockTracking()) continue;

                $recipe = \App\Models\InvRecipe::where('product_id', $product->id)->active()->with('items.item')->first();
                if ($recipe) {
                    foreach ($recipe->items as $ri) {
                        if (!$ri->item) continue;
                        $needed = ($ri->quantity_net / max(0.01, $recipe->portions)) * $oi['quantity'];
                        if ($ri->item->stock < $needed) {
                            $stockErrors[] = "{$ri->item->name} (necesita {$needed}, stock: {$ri->item->stock})";
                        }
                    }
                } else {
                    $links = InvProductItem::where('product_id', $product->id)->get();
                    foreach ($links as $link) {
                        $invItem = InvItem::find($link->item_id);
                        if (!$invItem) continue;
                        $needed = $link->quantity * $oi['quantity'];
                        if ($invItem->stock < $needed) {
                            $stockErrors[] = "{$invItem->name} (necesita {$needed}, stock: {$invItem->stock})";
                        }
                    }
                }
            }
        }

        if (!empty($stockErrors)) {
            $msg = 'Stock insuficiente: ' . implode('; ', array_unique($stockErrors));
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if ($request->order_id) {
            $order = PosOrder::where('seller_id', $seller->id)->findOrFail($request->order_id);

            if ($order->pos_table_id) {
                PosTable::where('seller_id', $seller->id)->where('id', $order->pos_table_id)->update(['status' => 'occupied']);
            }

            foreach ($orderItems as $oi) {
                $oi['pos_order_id'] = $order->id;
                PosOrderItem::create($oi);
            }

            $order->subtotal += $subtotal;
            $order->total += $subtotal;
            if ($request->pos_staff_id) {
                $order->pos_staff_id = $request->pos_staff_id;
            }
            if ($request->kitchen_notes) {
                $order->kitchen_notes = trim(($order->kitchen_notes ? $order->kitchen_notes . ' | ' : '') . $request->kitchen_notes);
            }
            $order->save();

            if ($isCourtesy && $order->total <= 0) {
                $order->update(['payment_status' => 'paid', 'payment_method' => 'courtesy', 'paid_at' => now()]);
            }

            if (!$isCourtesy) {
                $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
                if ($cashSession) {
                    $transaction = PosTransaction::where('pos_order_id', $order->id)->where('type', 'sale')->first();
                    if ($transaction) {
                        $transaction->amount += $subtotal;
                        $transaction->description = 'Venta #' . $order->order_no . ' + Adiciones';
                        $transaction->save();
                    } else {
                        PosTransaction::create([
                            'cash_session_id' => $cashSession->id,
                            'seller_id'       => $seller->id,
                            'pos_order_id'    => $order->id,
                            'type'            => 'sale',
                            'amount'          => $subtotal,
                            'description'     => 'Adición a Venta #' . $order->order_no,
                            'payment_method'  => $order->order_type === 'delivery' ? 'delivery' : 'cash',
                        ]);
                    }
                    $cashSession->increment('total_sales', $subtotal);
                }
            }

            if ($store) {
                $store->dispatchWebhook('order.updated', [
                    'id'              => $order->id,
                    'order_no'        => $order->order_no,
                    'customer_name'   => $order->customer_name,
                    'customer_phone'  => $order->customer_phone,
                    'total'           => (float) $order->total,
                    'order_type'      => $order->order_type,
                    'status'          => $order->status,
                    'payment_status'  => $order->payment_status,
                    'created_at'      => $order->created_at->toIso8601String(),
                ]);
            }

            return redirect()->route('seller.pos')->with('success', 'Adición a comanda #' . $order->order_no . ' enviada a cocina');
        }

        $deliveryFee = 0;
        $deliveryTypes = ['delivery', 'daz', 'llama', 'rappi', 'pedidosya', 'lizto_delivery'];
        if (in_array($request->order_type, $deliveryTypes) && $store && $request->delivery_lat && $request->delivery_lng) {
            $estimate = DeliveryPricing::estimateForStore($store, (float) $request->delivery_lat, (float) $request->delivery_lng);
            $deliveryFee = $estimate['delivery_fee'] ?? 0;
        }

        if ($request->table_id) {
            PosTable::where('seller_id', $seller->id)->where('id', $request->table_id)->update(['status' => 'occupied']);
        }

        $orderNo = 'POS-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

        // Calculate discount
        $discount = 0;
        $discountType = $request->discount_type;
        $discountValue = floatval($request->discount_value ?? 0);
        if ($discountValue > 0 && !$isCourtesy) {
            if ($discountType === 'percent') {
                $discount = round($subtotal * min(100, $discountValue) / 100, 2);
            } else {
                $discount = min($subtotal, $discountValue);
            }
        }

        $orderData = [
            'seller_id'       => $seller->id,
            'store_id'        => $store?->id,
            'pos_table_id'    => $request->table_id,
            'pos_staff_id'    => $request->pos_staff_id,
            'order_no'        => $orderNo,
            'customer_name'   => $request->customer_name,
            'customer_phone'  => $request->customer_phone,
            'customer_doc_type' => $request->customer_doc_type,
            'customer_doc'    => $request->customer_doc,
            'delivery_address'=> $request->delivery_address,
            'delivery_lat'    => $request->delivery_lat,
            'delivery_lng'    => $request->delivery_lng,
            'delivery_fee'    => $deliveryFee,
            'subtotal'        => $subtotal,
            'discount'        => $discount,
            'total'           => $subtotal - $discount + $deliveryFee,
            'order_type'      => $request->order_type,
            'status'          => 'confirmed',
            'notes'           => $request->notes,
            'kitchen_notes'   => $request->kitchen_notes,
        ];

        if ($isCourtesy) {
            $orderData['payment_status'] = 'paid';
            $orderData['payment_method'] = 'courtesy';
            $orderData['paid_at'] = now();
        }

        $order = PosOrder::create($orderData);

        foreach ($orderItems as $oi) {
            $oi['pos_order_id'] = $order->id;
            PosOrderItem::create($oi);
        }

        // Register in cash session (skip for courtesy orders)
        if (!$isCourtesy) {
            $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
            if ($cashSession) {
                PosTransaction::create([
                    'cash_session_id' => $cashSession->id,
                    'seller_id' => $seller->id,
                    'pos_order_id' => $order->id,
                    'type' => 'sale',
                    'amount' => $order->total,
                    'description' => 'Venta #' . $order->order_no,
                    'payment_method' => $order->order_type === 'delivery' ? 'delivery' : 'cash',
                ]);
                $cashSession->increment('total_sales', $order->total);
            }
        }

        // Notify couriers if delivery order
        $notified = 0;
        if ($request->order_type === 'delivery') {
            $drivers = \App\Models\Driver::active()->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])->get();
            foreach ($drivers as $driver) {
                \App\Services\FcmService::sendToDriver($driver, 'Nuevo pedido delivery',
                    'Pedido #' . $orderNo . ' - ' . ($store?->name ?? 'Tienda') . ' | S/ ' . number_format($order->total, 2),
                    ['pos_order_id' => (string) $order->id, 'type' => 'pos_delivery', 'store_name' => $store?->name ?? '']
                );
                $notified++;
            }
        }
        if ($store) {
            $store->dispatchWebhook('order.created', [
                'id'              => $order->id,
                'order_no'        => $order->order_no,
                'customer_name'   => $order->customer_name,
                'customer_phone'  => $order->customer_phone,
                'total'           => (float) $order->total,
                'order_type'      => $order->order_type,
                'status'          => $order->status,
                'payment_status'  => $order->payment_status,
                'created_at'      => $order->created_at->toIso8601String(),
            ]);
        }

        $successMsg = 'Comanda #' . $orderNo . ' enviada a cocina' . ($notified > 0 ? ' y notificada a ' . $notified . ' repartidores' : '');
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg, 'order_no' => $orderNo]);
        }
        return redirect()->route('seller.pos')->with('success', $successMsg);
    }

    // ── Customers ──

    public function customers()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Clientes';
        $customers = PosOrder::where('seller_id', $seller->id)
            ->whereNotNull('customer_phone')
            ->selectRaw('customer_name, customer_phone, COUNT(*) as total_orders, SUM(total) as total_spent, MAX(created_at) as last_order')
            ->groupBy('customer_phone', 'customer_name')
            ->orderByDesc('total_orders')
            ->get();

        return view('seller.customers', compact('pageTitle', 'seller', 'store', 'customers'));
    }

    // ── Orders ──

    public function orders()
    {
        $seller = $this->seller();
        $store = $this->store();
        $storeId = $store?->id;
        $pageTitle = 'Pedidos';

        $posOrders = PosOrder::where('seller_id', $seller->id)->with('items', 'table')->latest()->get()->map(fn($o) => [
            'source' => 'POS', 'id' => $o->id, 'order_no' => $o->order_no, 'customer' => $o->customer_name,
            'type' => $o->order_type, 'table' => $o->table?->name, 'total' => $o->total,
            'status' => $o->status, 'items' => $o->items->count(), 'created_at' => $o->created_at,
            'cancel_reason' => $o->cancel_reason, 'is_pos' => true,
            'items_list' => $o->items->map(fn($i) => [
                'name' => $i->product_name ?? ($i->product ? $i->product->name : 'Producto'),
                'qty' => $i->quantity,
                'price' => $i->unit_price,
            ])->toArray(),
        ]);

        $delOrders = DeliveryOrder::where('store_id', $storeId)->with('user', 'items', 'driver')->latest()->get()->map(function($o) {
            $proof = \App\Models\CourierProof::where('job_id', $o->id)->where('job_type', 'delivery')->first();
            return [
                'source' => 'App', 'id' => $o->id, 'order_no' => $o->order_no, 'customer' => $o->user?->fullname,
                'type' => 'delivery', 'table' => null, 'total' => $o->total,
                'status' => $o->status, 'items' => $o->items->count(), 'created_at' => $o->created_at,
                'driver' => $o->driver?->fullname, 'is_pos' => false,
                'driver_phone' => $o->driver?->mobile,
                'driver_email' => $o->driver?->email,
                'delivery_proof' => $proof ? asset($proof->image) : null,
                'delivery_proof_note' => $proof ? $proof->note : null,
                'items_list' => $o->items->map(fn($i) => [
                    'name' => $i->product_name,
                    'qty' => $i->quantity,
                    'price' => $i->price,
                ])->toArray(),
            ];
        });

        $favors = collect();
        if ($store && $store->latitude && $store->longitude) {
            $favors = \App\Models\Favor::where('pickup_lat', $store->latitude)
                ->where('pickup_lng', $store->longitude)
                ->with('courier')
                ->latest()
                ->get()
                ->map(function($f) {
                    $proof = \App\Models\CourierProof::where('job_id', $f->id)->where('job_type', 'favor')->first();
                    return [
                        'source' => 'Envío', 'id' => $f->id, 'order_no' => $f->order_no, 'customer' => $f->recipient_name,
                        'type' => 'send', 'table' => null, 'total' => $f->total,
                        'status' => $f->status, 'items' => 1, 'created_at' => $f->created_at,
                        'driver' => $f->courier?->fullname, 'is_pos' => false, 'is_favor' => true,
                        'driver_phone' => $f->courier?->mobile,
                        'driver_email' => $f->courier?->email,
                        'delivery_proof' => $proof ? asset($proof->image) : null,
                        'delivery_proof_note' => $proof ? $proof->note : null,
                        'description' => $f->description,
                        'pickup_address' => $f->pickup_address,
                        'delivery_address' => $f->delivery_address,
                    ];
                });
        }

        $orders = $posOrders->concat($delOrders)->concat($favors)->sortByDesc('created_at')->values();

        $pusherConfig = [
            'key'     => env('PUSHER_APP_KEY', env('REVERB_APP_KEY', '')),
            'host'    => trim(env('REVERB_HOST', env('PUSHER_HOST', 'localhost')), '"'),
            'port'    => env('REVERB_PORT', env('PUSHER_PORT', 8080)),
            'scheme'  => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')),
            'cluster' => env('PUSHER_APP_CLUSTER', ''),
        ];

        return view('seller.orders', compact('pageTitle', 'seller', 'store', 'orders', 'pusherConfig'));
    }

    public function products()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Productos';

        // Get store's general categories for SUNAT code defaults
        $storeCategories = $store->subCategories->pluck('generalCategory.name')->filter()->values()->toArray();
        $storeType = $store->store_type ?? 'restaurant';

        // Get warehouses for stock adjustments
        $warehouses = \App\Models\InvWarehouse::where('seller_id', $seller->id)->get();

        return view('seller.products', compact('pageTitle', 'seller', 'store', 'storeCategories', 'storeType', 'warehouses'));
    }

    // ── Kitchen Display ──

    public function kitchen()
    {
        $this->requireRestaurant();
        $seller = $this->seller();
        $pageTitle = 'Cocina - Comandas';
        $store = $this->store();
        $storeId = $store?->id;

        $posOrders = PosOrder::where('seller_id', $seller->id)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('items', 'table')
            ->latest()
            ->get();

        $delOrdersRaw = $storeId ? DeliveryOrder::where('store_id', $storeId)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('items')
            ->latest()
            ->get() : collect();

        $delOrders = $delOrdersRaw->map(fn($o) => (object)[
            'id'            => $o->id,
            'order_no'      => $o->order_no,
            'order_type'    => 'app_delivery',
            'customer_name' => $o->contact_name,
            'status'        => $o->status,
            'kitchen_notes' => $o->notes,
            'total'         => $o->total,
            'table'         => null,
            'created_at'    => $o->created_at,
            'items'         => $o->items->map(fn($i) => (object)[
                'id' => $i->id, 'product_name' => $i->product_name,
                'quantity' => $i->quantity, 'status' => $i->status ?? 'pending',
                'notes' => $i->notes ?? null,
            ]),
            'source'        => 'delivery',
        ])->values();

        $orders = $posOrders->map(function($o) {
            $o->source = 'pos';
            return $o;
        })->concat($delOrders)->sortByDesc('created_at')->values();

        return view('seller.pos.kitchen', compact('pageTitle', 'seller', 'store', 'orders'));
    }

    public function kitchenUpdateStatus(Request $request, $id)
    {
        $this->requireRestaurant();
        $source = $request->source ?? 'pos';

        if ($source === 'delivery') {
            $storeId = $this->store()?->id;
            $order = DeliveryOrder::where('store_id', $storeId)->with('store', 'user')->findOrFail($id);
            $order->update(['status' => $request->status]);
            if ($request->has('item_id')) {
                DeliveryOrderItem::where('delivery_order_id', $order->id)
                    ->where('id', $request->item_id)
                    ->update(['status' => $request->item_status ?? $request->status]);
            }

            event(new DeliveryOrderStatusUpdated($order));

            if (in_array($request->status, ['confirmed', 'ready'])) {
                broadcast(new NewJobAvailable($order, 'Nuevo pedido para reparto en ' . ($order->store->name ?? 'tienda')))->toOthers();
                FcmService::sendToAllCouriers(
                    'Nuevo pedido disponible',
                    'Hay un pedido listo para reparto en ' . ($order->store->name ?? 'tu zona'),
                    ['job_id' => (string) $order->id, 'order_no' => $order->order_no, 'job_type' => 'delivery']
                );
            }

            if ($order->user) {
                $statusLabels = [
                    'confirmed' => ['Pedido confirmado', 'Tu pedido #' . $order->order_no . ' ha sido confirmado por la tienda'],
                    'preparing' => ['Preparando tu pedido', 'La tienda está preparando tu pedido #' . $order->order_no],
                    'ready'     => ['Pedido listo', 'Tu pedido #' . $order->order_no . ' está listo para ser entregado'],
                ];
                if (isset($statusLabels[$request->status])) {
                    FcmService::sendToUser($order->user, $statusLabels[$request->status][0], $statusLabels[$request->status][1], [
                        'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'order_status', 'status' => $request->status,
                        'store_name' => $order->store->name ?? '',
                    ]);
                }
            }
        } else {
            $order = PosOrder::where('seller_id', $this->seller()->id)->findOrFail($id);
            $order->update(['status' => $request->status]);
            if ($request->has('item_id')) {
                PosOrderItem::where('pos_order_id', $order->id)->where('id', $request->item_id)
                    ->update(['status' => $request->item_status ?? $request->status]);
            }
            if ($request->status === 'delivered' && $order->pos_table_id) {
                PosTable::where('id', $order->pos_table_id)
                    ->orWhere('linked_to_table_id', $order->pos_table_id)
                    ->update([
                        'status' => 'free',
                        'linked_to_table_id' => null
                    ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $request->status]);
        }

        return back()->with('success', 'Estado actualizado');
    }

    // ── API for POS (JSON) ──

    public function feeEstimate(Request $request)
    {
        $store = $this->store();
        if (!$store || !$request->lat || !$request->lng) {
            return response()->json(['delivery_fee' => 0]);
        }
        $estimate = DeliveryPricing::estimateForStore($store, (float) $request->lat, (float) $request->lng);
        return response()->json($estimate);
    }

    public function activeOrders()
    {
        $seller = $this->seller();
        $store = $this->store();
        $storeId = $store?->id;

        $posOrders = PosOrder::where('seller_id', $seller->id)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with(['items.product.category', 'table'])
            ->latest()
            ->get()
            ->map(function($o) {
                $station = 'kitchen';
                foreach ($o->items as $i) {
                    $prod = $i->product;
                    if ($prod && method_exists($prod, 'isBarProduct') && $prod->isBarProduct()) {
                        $station = 'bar';
                        break;
                    }
                    if ($prod && $prod->category && stripos($prod->category->name ?? '', 'bar') !== false) {
                        $station = 'bar';
                        break;
                    }
                }
                return [
                    'id'            => $o->id,
                    'order_no'      => $o->order_no,
                    'table'         => $o->table?->name,
                    'area'          => $o->table?->area,
                    'order_type'    => $o->order_type,
                    'customer_name' => $o->customer_name,
                    'status'        => $o->status,
                    'kitchen_notes' => $o->kitchen_notes,
                    'total'         => $o->total,
                    'source'        => 'pos',
                    'station'       => $station,
                    'items'         => $o->items->map(fn($i) => [
                        'id' => $i->id, 'name' => $i->product_name, 'qty' => $i->quantity, 'status' => $i->status, 'is_takeaway' => $i->is_takeaway, 'notes' => $i->notes
                    ]),
                    'items_count'   => $o->items->count(),
                    'created_at'    => $o->created_at->diffForHumans(),
                    'raw_date'      => $o->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $delOrders = collect();
        if ($storeId) {
            $delOrders = DeliveryOrder::where('store_id', $storeId)
                ->whereIn('status', ['confirmed', 'preparing', 'ready'])
                ->with(['items.product.category'])
                ->latest()
                ->get()
                ->map(function($o) {
                    $station = 'kitchen';
                    foreach ($o->items as $i) {
                        $prod = $i->product;
                        if ($prod && method_exists($prod, 'isBarProduct') && $prod->isBarProduct()) {
                            $station = 'bar';
                            break;
                        }
                        if ($prod && $prod->category && stripos($prod->category->name ?? '', 'bar') !== false) {
                            $station = 'bar';
                            break;
                        }
                    }
                    return [
                        'id'            => $o->id,
                        'order_no'      => $o->order_no,
                        'table'         => null,
                        'area'          => null,
                        'order_type'    => 'app_delivery',
                        'customer_name' => $o->contact_name,
                        'status'        => $o->status,
                        'kitchen_notes' => $o->notes,
                        'total'         => $o->total,
                        'source'        => 'delivery',
                        'station'       => $station,
                        'items'         => $o->items->map(fn($i) => [
                            'id' => $i->id, 'name' => $i->product_name, 'qty' => $i->quantity, 'status' => $i->status ?? 'pending'
                        ]),
                        'items_count'   => $o->items->count(),
                        'created_at'    => $o->created_at->diffForHumans(),
                        'raw_date'      => $o->created_at->format('Y-m-d H:i:s'),
                    ];
                });
        }

        $orders = $posOrders->concat($delOrders)->sortByDesc('raw_date')->values();

        return response()->json($orders);
    }

    public function getActiveTableOrder($tableId)
    {
        $seller = $this->seller();
        $table = PosTable::where('seller_id', $seller->id)->findOrFail($tableId);
        $targetTableId = $table->linked_to_table_id ?: $table->id;

        $order = PosOrder::where('seller_id', $seller->id)
            ->where('pos_table_id', $targetTableId)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('items')
            ->latest()
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'No active order for this table']);
        }

        return response()->json([
            'status' => true,
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_doc_type' => $order->customer_doc_type,
                'customer_doc' => $order->customer_doc,
                'notes' => $order->notes,
                'kitchen_notes' => $order->kitchen_notes,
                'pos_staff_id' => $order->pos_staff_id,
                'total' => (float)$order->total,
                'subtotal' => (float)$order->subtotal,
                'items' => $order->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'name' => $i->product_name,
                    'qty' => $i->quantity,
                    'price' => (float)$i->unit_price,
                    'total' => (float)$i->total_price,
                    'status' => $i->status,
                    'is_takeaway' => (bool)$i->is_takeaway,
                    'notes' => $i->notes
                ])
            ]
        ]);
    }

    // ── SUNAT Customer Lookup ──

    public function sunatLookup(Request $request)
    {
        $numdoc = trim($request->numdoc ?? '');
        $tpdoc  = trim($request->tpdoc ?? '');

        if (empty($numdoc) || empty($tpdoc)) {
            return response()->json(['status' => false, 'result' => 'Número y tipo de documento requeridos']);
        }

        $url = 'https://app.startfact.com.pe/backend/seller/rest/buscarsunat?numdoc=' . urlencode($numdoc)
            . '&tpdoc=' . urlencode($tpdoc);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => ['X-API-KEY: 24a4b02d0fa8f0237735dad1'],
        ]);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return response()->json(['status' => false, 'result' => 'Error de conexión: ' . $error]);
        }

        if ($httpCode === 0 || !$response) {
            return response()->json(['status' => false, 'result' => 'No se pudo conectar al servicio SUNAT. Verifica tu conexión.']);
        }

        if ($httpCode !== 200) {
            return response()->json(['status' => false, 'result' => 'Servicio no disponible (HTTP ' . $httpCode . ')']);
        }

        $api = json_decode($response, true);
        if (!$api) {
            return response()->json(['status' => false, 'result' => 'Respuesta inválida del servicio']);
        }

        if ((isset($api['success']) && $api['success'] === false) || (isset($api['status']) && $api['status'] === 'false')) {
            return response()->json(['status' => false, 'result' => $api['message'] ?? ($api['result'] ?? 'No encontrado')]);
        }

        if ($tpdoc == '1' && isset($api['data'])) {
            return response()->json([
                'status' => true,
                'nombre' => $api['data']['nombreCompleto'] ?? '',
                'nombres' => $api['data']['nombre'] ?? '',
                'apellidoPaterno' => $api['data']['apellidoPaterno'] ?? '',
                'apellidoMaterno' => $api['data']['apellidoMaterno'] ?? '',
                'numeroDocumento' => $api['data']['numdoc'] ?? $numdoc,
            ]);
        }

        if ($tpdoc == '6' && isset($api['data'])) {
            return response()->json([
                'status' => true,
                'nombre' => $api['data']['razonSocial'] ?? '',
                'nombreComercial' => $api['data']['ncomercial'] ?? '',
                'direccion' => $api['data']['direccion'] ?? '',
                'numeroDocumento' => $api['data']['ruc'] ?? $numdoc,
            ]);
        }

        return response()->json(['status' => false, 'result' => 'Tipo de documento no válido o datos no disponibles']);
    }

    // ── Reports ──

    public function reports()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Reportes';
        $storeId = $store?->id;

        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $sellerFilter = request('seller_filter');

        $posData = PosOrder::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        $delData = DeliveryOrder::where('store_id', $storeId)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($sellerFilter) {
            $posData->where('seller_id', $sellerFilter);
        }

        $report = [
            'from' => $dateFrom, 'to' => $dateTo,
            'pos_count'  => (clone $posData)->count(),
            'pos_sales'  => (clone $posData)->sum('total'),
            'del_count'  => (clone $delData)->count(),
            'del_sales'  => (clone $delData)->sum('total'),
            'total_orders' => (clone $posData)->count() + (clone $delData)->count(),
            'total_sales' => (clone $posData)->sum('total') + (clone $delData)->sum('total'),
            'by_type' => (clone $posData)->selectRaw("order_type, COUNT(*) as c, SUM(total) as s")->groupBy('order_type')->get(),
        ];

        $ordersQuery = PosOrder::where('seller_id', $seller->id)->with('table')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if ($sellerFilter) {
            $ordersQuery->where('seller_id', $sellerFilter);
        }

        $orders = $ordersQuery->latest()->paginate(20);

        $sellers = \App\Models\Seller::whereHas('stores', fn($q) => $q->where('id', $storeId))->get(['id', 'name']);

        return view('seller.reports', compact('pageTitle', 'seller', 'store', 'report', 'orders', 'dateFrom', 'dateTo', 'sellers'));
    }

    public function reportsExportExcel()
    {
        $seller = $this->seller();
        $store = $this->store();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));

        $orders = PosOrder::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('table')->latest()->get();

        $csv = "Pedido,Cliente,Tipo,Mesa,Items,Total,Estado,Fecha\n";
        foreach ($orders as $o) {
            $csv .= implode(',', [
                $o->order_no,
                '"' . ($o->customer_name ?? '') . '"',
                $o->order_type,
                $o->table?->name ?? '',
                $o->items->count(),
                number_format($o->total, 2),
                $o->status,
                $o->created_at->format('d/m/Y H:i'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reporte_ventas_' . $dateFrom . '_' . $dateTo . '.csv"',
        ]);
    }

    public function reportsExportPdf()
    {
        $seller = $this->seller();
        $store = $this->store();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));

        $orders = PosOrder::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('table')->latest()->get();

        $totalSales = $orders->sum('total');
        $totalOrders = $orders->count();

        $html = "<html><head><style>
            body{font-family:Arial;font-size:12px;color:#333}
            h1{font-size:18px;color:#22c55e}h2{font-size:14px;color:#555}
            table{width:100%;border-collapse:collapse;margin:16px 0}
            th{background:#22c55e;color:#fff;padding:8px;text-align:left;font-size:11px}
            td{padding:6px 8px;border-bottom:1px solid #eee;font-size:11px}
            .summary{display:flex;gap:24px;margin:12px 0}.summary div{background:#f8f9fa;padding:12px;border-radius:8px;flex:1;text-align:center}
            .summary b{font-size:16px;color:#22c55e;display:block}
        </style></head><body>
        <h1>Reporte de Ventas</h1>
        <h2>{$store?->name} — {$dateFrom} al {$dateTo}</h2>
        <div class='summary'>
            <div><b>S/ " . number_format($totalSales, 2) . "</b>Total Ventas</div>
            <div><b>{$totalOrders}</b>Total Pedidos</div>
            <div><b>S/ " . ($totalOrders > 0 ? number_format($totalSales / $totalOrders, 2) : '0.00') . "</b>Ticket Promedio</div>
        </div>
        <table>
            <tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Mesa</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>";

        foreach ($orders as $o) {
            $html .= "<tr>
                <td>{$o->order_no}</td>
                <td>" . ($o->customer_name ?: '—') . "</td>
                <td>{$o->order_type}</td>
                <td>" . ($o->table?->name ?? '—') . "</td>
                <td><b>S/ " . number_format($o->total, 2) . "</b></td>
                <td>{$o->status}</td>
                <td>{$o->created_at->format('d/m H:i')}</td>
            </tr>";
        }

        $html .= "</table></body></html>";

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="reporte_ventas_' . $dateFrom . '_' . $dateTo . '.html"',
        ]);
    }

    public function reportsAdvanced()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Reportes Avanzados';
        $storeId = $store?->id;

        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));

        $activeCompany = $this->activeCompany();

        // Sales by product
        $byProduct = PosOrderItem::whereHas('order', fn($q) => $q->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->selectRaw('product_name, SUM(quantity) as qty, SUM(unit_price * quantity) as total')
            ->groupBy('product_name')->orderByDesc('total')->limit(20)->get();

        // Sales by category
        $byCategory = PosOrderItem::whereHas('order', fn($q) => $q->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->join('products', 'products.id', '=', 'pos_order_items.product_id')
            ->join('store_categories', 'store_categories.id', '=', 'products.store_category_id')
            ->selectRaw('store_categories.name as cat_name, SUM(pos_order_items.quantity) as qty, SUM(pos_order_items.unit_price * pos_order_items.quantity) as total')
            ->groupBy('store_categories.name')->orderByDesc('total')->get();

        // Sales by hour
        $byHour = PosOrder::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('HOUR(created_at) as hora, COUNT(*) as c, SUM(total) as s')
            ->groupBy('hora')->orderBy('hora')->get();

        // Cash sessions
        $cashSessions = PosCashSession::where('seller_id', $seller->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->latest()->get();

        // Expenses by category
        $expenses = PosExpense::where('seller_id', $seller->id)
            ->whereBetween('expense_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')->get();

        // SUNAT invoices
        $sunatInvoicesQuery = SunatInvoice::whereBetween('fecha_emision', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        if ($activeCompany) {
            $sunatInvoicesQuery->where(function($q) use ($activeCompany, $seller) {
                $q->where('seller_company_id', $activeCompany->id)
                  ->orWhere(function($sq) use ($seller) {
                      $sq->where('seller_id', $seller->id)->whereNull('seller_company_id');
                  });
            });
        } else {
            $sunatInvoicesQuery->where('seller_id', $seller->id);
        }
        $sunatInvoices = $sunatInvoicesQuery->latest()->get();

        // Waste from voided prepared products
        $wasteByProduct = PosOrderItem::whereHas('order', fn($q) => $q->where('seller_id', $seller->id)
            ->where('status', 'cancelled')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']))
            ->join('products', 'products.id', '=', 'pos_order_items.product_id')
            ->where('products.stock_type', 'prepared')
            ->selectRaw('pos_order_items.product_name, SUM(pos_order_items.quantity) as qty, SUM(pos_order_items.quantity * products.price) as cost')
            ->groupBy('pos_order_items.product_name')->orderByDesc('cost')->get();

        // Sales by platform (POS, DAZ, LLAMA, RAPPI, etc.)
        $byPlatform = PosOrder::where('seller_id', $seller->id)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('order_type, COUNT(*) as count, SUM(total) as total')
            ->groupBy('order_type')->orderByDesc('total')->get();

        return view('seller.reports_advanced', compact('pageTitle', 'seller', 'store', 'dateFrom', 'dateTo',
            'byProduct', 'byCategory', 'byHour', 'cashSessions', 'expenses', 'sunatInvoices', 'wasteByProduct', 'byPlatform', 'activeCompany'));
    }

    /** Centro de trabajo tributario. Los XLSX son de revisión y el TXT es el
     * archivo plano para reemplazar la propuesta en SIRE. */
    public function declarations()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Declaraciones SUNAT / SIRE';
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $activeCompany = $this->activeCompany();

        $sales = SunatInvoice::where('seller_id', $seller->id)
            ->where('cdr_status', SunatInvoice::STATUS_ACCEPTED)
            ->whereBetween('fecha_emision', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        if ($activeCompany) {
            $sales->where(function ($query) use ($activeCompany) {
                $query->where('seller_company_id', $activeCompany->id)
                    ->orWhereNull('seller_company_id');
            });
        }

        $purchases = InvPurchase::where('seller_id', $seller->id)
            ->whereBetween('document_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        return view('seller.declarations', compact('pageTitle', 'seller', 'store', 'dateFrom', 'dateTo', 'activeCompany') + [
            'salesCount' => $sales->count(), 'salesTotal' => (float) $sales->sum('total'),
            'purchasesCount' => $purchases->count(), 'purchasesTotal' => (float) $purchases->sum('total'),
        ]);
    }

    public function exportRVIE()
    {
        $seller = $this->seller();
        $activeCompany = $this->activeCompany();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $format = request('format', 'excel');

        $invoicesQuery = SunatInvoice::with('company')
            ->whereBetween('fecha_emision', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('cdr_status', SunatInvoice::STATUS_ACCEPTED)
            ->whereIn('tipo_doc', ['01', '03', '07', '08']);

        if ($activeCompany) {
            $invoicesQuery->where(function($q) use ($activeCompany, $seller) {
                $q->where('seller_company_id', $activeCompany->id)
                  ->orWhere(function($sq) use ($seller) {
                      $sq->where('seller_id', $seller->id)->whereNull('seller_company_id');
                  });
            });
        } else {
            $invoicesQuery->where('seller_id', $seller->id);
        }

        $invoices = $invoicesQuery->orderBy('fecha_emision')->get();

        // Las notas comparten pedido con el comprobante afectado. Se arma un
        // índice sin depender de que el comprobante original esté en el período.
        $affectedByOrder = SunatInvoice::whereIn('pos_order_id', $invoices->pluck('pos_order_id')->filter()->unique())
            ->whereIn('tipo_doc', ['01', '03'])
            ->orderBy('id')
            ->get()
            ->groupBy('pos_order_id')
            ->map(fn ($documents) => $documents->first());

        $company = $activeCompany ?? $seller;

        if ($format === 'txt') {
            $lines = [];
            foreach ($invoices as $inv) {
                $lines[] = implode('|', [
                    $inv->fecha_emision?->format('Ymd'),
                    $inv->tipo_doc,
                    $inv->serie,
                    str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT),
                    $inv->cliente_tipo_doc ?? '6',
                    $inv->cliente_num_doc ?? '00000000',
                    $inv->cliente_nombre,
                    number_format($inv->total_gravada ?? 0, 2, '.', ''),
                    number_format($inv->total_igv ?? 0, 2, '.', ''),
                    number_format($inv->total ?? 0, 2, '.', ''),
                    'PEN',
                    $inv->cdr_status,
                ]);
            }
            $content = implode("\r\n", $lines);
            $filename = 'RVIE_' . ($company->document_number ?? '0') . '_' . $dateFrom . '_' . $dateTo . '.txt';
            return response()->make($content, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('RVIE');

        $headers = ['Fecha', 'Tipo Doc', 'Serie', 'Número', 'Tipo Doc Cliente', 'N° Doc Cliente', 'Razón Social', 'Base Imponible', 'IGV', 'Total', 'Moneda', 'Estado'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }

        $row = 2;
        foreach ($invoices as $inv) {
            $i = 0;
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->fecha_emision?->format('d/m/Y'));
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->tipo_doc === '01' ? 'Factura' : ($inv->tipo_doc === '03' ? 'Boleta' : 'NC/ND'));
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->serie);
            $sheet->setCellValue(chr(65 + $i++) . $row, str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT));
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->cliente_tipo_doc === '6' ? 'RUC' : 'DNI');
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->cliente_num_doc);
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->cliente_nombre);
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->total_gravada ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->total_igv ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->total ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->moneda ?? 'PEN');
            $sheet->setCellValue(chr(65 + $i++) . $row, $inv->statusLabel());
            $row++;
        }

        // El exportador anterior era un resumen. Se reemplaza su contenido por
        // el diseño contable del Formato 14.1, conservando identificadores como texto.
        $sheet->removeRow(1, $row);
        $period = \Carbon\Carbon::parse($dateFrom)->format('Ym') . '00';
        $companyName = $company->business_name ?? $company->name ?? trim(($company->firstname ?? '') . ' ' . ($company->lastname ?? ''));
        $ruc = $company->document_number ?? $company->ruc ?? '';

        $sheet->mergeCells('A1:V1')->setCellValue('A1', 'FORMATO 14.1: REGISTRO DE VENTAS E INGRESOS');
        $sheet->mergeCells('A2:V2')->setCellValue('A2', 'PERIODO: ' . $period . ' - ' . mb_strtoupper(\Carbon\Carbon::parse($dateFrom)->locale('es')->translatedFormat('F Y')));
        $sheet->mergeCells('A3:V3')->setCellValue('A3', 'RUC: ' . $ruc);
        $sheet->mergeCells('A4:V4')->setCellValue('A4', 'APELLIDOS Y NOMBRES, DENOMINACIÓN O RAZÓN SOCIAL: ' . $companyName);

        foreach (['A6:A8','B6:B8','C6:C8','D6:F6','G6:I6','J6:J8','K6:K8','L6:M6','N6:N8','O6:O8','P6:P8','Q6:Q8','R6:R8','S6:V6','G7:H7'] as $range) {
            $sheet->mergeCells($range);
        }
        $sheet->setCellValue('A6', 'NÚMERO CORRELATIVO DEL REGISTRO O CÓDIGO ÚNICO DE LA OPERACIÓN');
        $sheet->setCellValue('B6', 'FECHA DE EMISIÓN DEL COMPROBANTE DE PAGO O DOCUMENTO');
        $sheet->setCellValue('C6', 'FECHA DE VENCIMIENTO Y/O PAGO');
        $sheet->setCellValue('D6', 'COMPROBANTE DE PAGO O DOCUMENTO');
        $sheet->setCellValue('G6', 'INFORMACIÓN DEL CLIENTE');
        $sheet->setCellValue('J6', 'VALOR FACTURADO DE LA EXPORTACIÓN');
        $sheet->setCellValue('K6', 'BASE IMPONIBLE DE LA OPERACIÓN GRAVADA');
        $sheet->setCellValue('L6', 'IMPORTE TOTAL DE LA OPERACIÓN EXONERADA O INAFECTA');
        $sheet->setCellValue('N6', 'ISC');
        $sheet->setCellValue('O6', 'IGV Y/O IPM');
        $sheet->setCellValue('P6', 'OTROS TRIBUTOS Y CARGOS QUE NO FORMAN PARTE DE LA BASE IMPONIBLE');
        $sheet->setCellValue('Q6', 'IMPORTE TOTAL DEL COMPROBANTE DE PAGO');
        $sheet->setCellValue('R6', 'TIPO DE CAMBIO');
        $sheet->setCellValue('S6', 'REFERENCIA DEL COMPROBANTE DE PAGO O DOCUMENTO ORIGINAL QUE SE MODIFICA');
        foreach (['D7'=>'TIPO (TABLA 10)','E7'=>'N° SERIE','F7'=>'NÚMERO','G7'=>'DOCUMENTO DE IDENTIDAD','I7'=>'APELLIDOS Y NOMBRES, DENOMINACIÓN O RAZÓN SOCIAL','L7'=>'EXONERADA','M7'=>'INAFECTA','S7'=>'FECHA','T7'=>'TIPO (TABLA 10)','U7'=>'SERIE','V7'=>'N° DEL COMPROBANTE DE PAGO O DOCUMENTO','G8'=>'TIPO (TABLA 2)','H8'=>'NÚMERO'] as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $excelRow = 9;
        foreach ($invoices as $index => $inv) {
            $isCreditNote = $inv->tipo_doc === '07';
            $sign = $isCreditNote ? -1 : 1;
            $affected = in_array($inv->tipo_doc, ['07', '08']) ? $affectedByOrder->get($inv->pos_order_id) : null;
            // Las notas creadas antes de registrar los totales por impuesto
            // usan como respaldo los importes del comprobante afectado.
            $taxable = $inv->total_gravada ?: ($affected?->total_gravada ?? 0);
            $exempt = $inv->total_exonerada ?: ($affected?->total_exonerada ?? 0);
            $unaffected = $inv->total_inafecta ?: ($affected?->total_inafecta ?? 0);
            $igv = $inv->total_igv ?: ($affected?->total_igv ?? 0);
            $sheet->setCellValueExplicit('A' . $excelRow, str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            foreach (['B', 'C'] as $column) $sheet->setCellValue($column . $excelRow, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($inv->fecha_emision));
            $sheet->setCellValueExplicit('D' . $excelRow, $inv->tipo_doc, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $excelRow, $inv->serie, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $excelRow, str_pad((string) $inv->correlativo, 8, '0', STR_PAD_LEFT), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $excelRow, $inv->cliente_tipo_doc ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H' . $excelRow, $inv->cliente_num_doc ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('I' . $excelRow, $inv->cliente_nombre ?? '');
            foreach (['J'=>0, 'K'=>$taxable, 'L'=>$exempt, 'M'=>$unaffected, 'N'=>0, 'O'=>$igv, 'P'=>0, 'Q'=>$inv->total ?? 0] as $column => $amount) $sheet->setCellValue($column . $excelRow, $amount * $sign);
            $sheet->setCellValue('R' . $excelRow, ($inv->moneda ?? 'PEN') === 'PEN' ? 1 : '');
            if ($affected) {
                $sheet->setCellValue('S' . $excelRow, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($affected->fecha_emision));
                $sheet->setCellValueExplicit('T' . $excelRow, $inv->note_affected_type ?: $affected->tipo_doc, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('U' . $excelRow, $affected->serie, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('V' . $excelRow, str_pad((string) $affected->correlativo, 8, '0', STR_PAD_LEFT), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $excelRow++;
        }
        $sheet->freezePane('A9');
        $sheet->getStyle('A1:V4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A6:V8')->applyFromArray(['font' => ['bold' => true, 'size' => 8], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9EAF7']], 'borders' => ['allBorders' => ['borderStyle' => 'thin']]]);
        $sheet->getStyle('B9:C' . max(9, $excelRow - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('S9:S' . max(9, $excelRow - 1))->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('J9:Q' . max(9, $excelRow - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getRowDimension(6)->setRowHeight(42);
        $sheet->getRowDimension(7)->setRowHeight(30);
        $sheet->getRowDimension(8)->setRowHeight(28);
        foreach (range('A', 'V') as $column) $sheet->getColumnDimension($column)->setWidth(15);
        $sheet->getColumnDimension('A')->setWidth(18); $sheet->getColumnDimension('B')->setWidth(16); $sheet->getColumnDimension('C')->setWidth(16); $sheet->getColumnDimension('I')->setWidth(34); $sheet->getColumnDimension('P')->setWidth(18);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'RVIE_' . ($company->document_number ?? '0') . '_' . $dateFrom . '_' . $dateTo . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportRCE()
    {
        $seller = $this->seller();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $format = request('format', 'excel');

        $purchases = InvPurchase::where('seller_id', $seller->id)
            ->with('supplier', 'items')
            ->whereBetween('document_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('document_date')->get();

        if ($format === 'txt') {
            $lines = [];
            foreach ($purchases as $p) {
                $lines[] = implode('|', [
                    $p->document_date?->format('Ymd'),
                    $p->document_type ?? '01',
                    $p->document_series ?? '',
                    $p->document_number ?? '',
                    $p->supplier->document_type ?? '6',
                    $p->supplier->document_number ?? '00000000',
                    $p->supplier->name ?? 'SIN NOMBRE',
                    number_format($p->subtotal ?? 0, 2, '.', ''),
                    number_format($p->igv ?? 0, 2, '.', ''),
                    number_format($p->total ?? 0, 2, '.', ''),
                    'PEN',
                ]);
            }
            $content = implode("\r\n", $lines);
            $filename = 'RCE_' . $dateFrom . '_' . $dateTo . '.txt';
            return response()->make($content, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('RCE');

        $headers = ['Fecha', 'Tipo Doc', 'Serie', 'Número', 'Tipo Doc Prov', 'N° Doc Prov', 'Proveedor', 'Base Imponible', 'IGV', 'Total', 'Moneda'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }

        $row = 2;
        foreach ($purchases as $p) {
            $i = 0;
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->document_date?->format('d/m/Y'));
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->document_type ?? '01');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->document_series ?? '');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->document_number ?? '');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->supplier->document_type ?? '6');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->supplier->document_number ?? '');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->supplier->name ?? '');
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->subtotal ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->igv ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, $p->total ?? 0);
            $sheet->setCellValue(chr(65 + $i++) . $row, 'PEN');
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'RCE_' . $dateFrom . '_' . $dateTo . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    // Compatibilidad con enlaces antiguos.
    public function exportREC()
    {
        return $this->exportRCE();
    }

    public function exportDiario()
    {
        $seller = $this->seller();
        $activeCompany = $this->activeCompany();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $format = request('format', 'excel');

        $invoicesQuery = SunatInvoice::with('company')
            ->whereBetween('fecha_emision', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('cdr_status', SunatInvoice::STATUS_ACCEPTED)
            ->whereIn('tipo_doc', ['01', '03', '07', '08']);

        if ($activeCompany) {
            $invoicesQuery->where(function($q) use ($activeCompany, $seller) {
                $q->where('seller_company_id', $activeCompany->id)
                  ->orWhere(function($sq) use ($seller) {
                      $sq->where('seller_id', $seller->id)->whereNull('seller_company_id');
                  });
            });
        } else {
            $invoicesQuery->where('seller_id', $seller->id);
        }

        $invoices = $invoicesQuery->orderBy('fecha_emision')->get();

        $company = $activeCompany ?? $seller;
        $ruc = $company->document_number ?? '00000000000';

        // Build accounting entries
        $entries = [];
        foreach ($invoices as $inv) {
            $period = $inv->fecha_emision?->format('Ym') . '00';
            $cuo = 'CUO' . str_pad($inv->id, 7, '0', STR_PAD_LEFT);
            $dateStr = $inv->fecha_emision?->format('d/m/Y');
            $desc = 'VENTA COMPROBANTE ' . $inv->serie . '-' . str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT);

            // Entry 1: 1212 (Accounts Receivable) - DEBIT
            $entries[] = [
                'period' => $period,
                'cuo' => $cuo,
                'seat' => 'A001',
                'account' => '1212',
                'date' => $dateStr,
                'desc' => $desc,
                'debit' => $inv->total,
                'credit' => 0.00,
                'doc_type' => $inv->tipo_doc,
                'serie' => $inv->serie,
                'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
            ];

            // Entry 2: 40111 (IGV) - CREDIT
            if (($inv->total_igv ?? 0) > 0) {
                $entries[] = [
                    'period' => $period,
                    'cuo' => $cuo,
                    'seat' => 'A002',
                    'account' => '40111',
                    'date' => $dateStr,
                    'desc' => $desc,
                    'debit' => 0.00,
                    'credit' => $inv->total_igv,
                    'doc_type' => $inv->tipo_doc,
                    'serie' => $inv->serie,
                    'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
                ];
            }

            // Entry 3: 70121 (Revenue) - CREDIT
            $entries[] = [
                'period' => $period,
                'cuo' => $cuo,
                'seat' => 'A003',
                'account' => '70121',
                'date' => $dateStr,
                'desc' => $desc,
                'debit' => 0.00,
                'credit' => $inv->total_gravada ?? $inv->total,
                'doc_type' => $inv->tipo_doc,
                'serie' => $inv->serie,
                'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
            ];
        }

        if ($format === 'txt') {
            $lines = [];
            foreach ($entries as $e) {
                $lines[] = implode('|', [
                    $e['period'],
                    $e['cuo'],
                    $e['seat'],
                    $e['account'],
                    '', // Código Unidad Operación
                    '', // Código Centro Costos
                    'PEN',
                    '', // Tipo Doc Identidad Emisor
                    '', // N° Doc Identidad Emisor
                    $e['doc_type'],
                    $e['serie'],
                    $e['number'],
                    $e['date'],
                    $e['date'], // Vencimiento
                    $e['date'], // Emisión
                    $e['desc'],
                    '', // Referencial
                    number_format($e['debit'], 2, '.', ''),
                    number_format($e['credit'], 2, '.', ''),
                    '', // Libro asociado
                    '1' // Estado
                ]) . '|';
            }
            $content = implode("\r\n", $lines);
            $filename = 'LE' . $ruc . $dateFrom . '0501001111.txt'; // PLE Libro Diario filename
            return response()->make($content, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Libro Diario');

        $headers = ['Periodo', 'CUO', 'Correlativo', 'Cuenta', 'Fecha', 'Glosa / Descripción', 'Debe', 'Haber', 'Tipo Doc', 'Serie', 'Número'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }

        $row = 2;
        foreach ($entries as $e) {
            $i = 0;
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['period']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['cuo']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['seat']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['account']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['date']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['desc']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['debit']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['credit']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['doc_type']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['serie']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['number']);
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Libro_Diario_' . $ruc . '_' . $dateFrom . '_' . $dateTo . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function exportMayor()
    {
        $seller = $this->seller();
        $activeCompany = $this->activeCompany();
        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));
        $format = request('format', 'excel');

        $invoicesQuery = SunatInvoice::with('company')
            ->whereBetween('fecha_emision', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('cdr_status', SunatInvoice::STATUS_ACCEPTED)
            ->whereIn('tipo_doc', ['01', '03', '07', '08']);

        if ($activeCompany) {
            $invoicesQuery->where(function($q) use ($activeCompany, $seller) {
                $q->where('seller_company_id', $activeCompany->id)
                  ->orWhere(function($sq) use ($seller) {
                      $sq->where('seller_id', $seller->id)->whereNull('seller_company_id');
                  });
            });
        } else {
            $invoicesQuery->where('seller_id', $seller->id);
        }

        $invoices = $invoicesQuery->orderBy('fecha_emision')->get();

        $company = $activeCompany ?? $seller;
        $ruc = $company->document_number ?? '00000000000';

        // Build accounting entries and sort by Account then Date
        $entries = [];
        foreach ($invoices as $inv) {
            $period = $inv->fecha_emision?->format('Ym') . '00';
            $cuo = 'CUO' . str_pad($inv->id, 7, '0', STR_PAD_LEFT);
            $dateStr = $inv->fecha_emision?->format('d/m/Y');
            $desc = 'VENTA COMPROBANTE ' . $inv->serie . '-' . str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT);

            // Entry 1: 1212
            $entries[] = [
                'period' => $period,
                'cuo' => $cuo,
                'seat' => 'A001',
                'account' => '1212',
                'date' => $dateStr,
                'desc' => $desc,
                'debit' => $inv->total,
                'credit' => 0.00,
                'doc_type' => $inv->tipo_doc,
                'serie' => $inv->serie,
                'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
            ];

            // Entry 2: 40111
            if (($inv->total_igv ?? 0) > 0) {
                $entries[] = [
                    'period' => $period,
                    'cuo' => $cuo,
                    'seat' => 'A002',
                    'account' => '40111',
                    'date' => $dateStr,
                    'desc' => $desc,
                    'debit' => 0.00,
                    'credit' => $inv->total_igv,
                    'doc_type' => $inv->tipo_doc,
                    'serie' => $inv->serie,
                    'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
                ];
            }

            // Entry 3: 70121
            $entries[] = [
                'period' => $period,
                'cuo' => $cuo,
                'seat' => 'A003',
                'account' => '70121',
                'date' => $dateStr,
                'desc' => $desc,
                'debit' => 0.00,
                'credit' => $inv->total_gravada ?? $inv->total,
                'doc_type' => $inv->tipo_doc,
                'serie' => $inv->serie,
                'number' => str_pad($inv->correlativo, 8, '0', STR_PAD_LEFT)
            ];
        }

        // Sort entries by account code
        usort($entries, function($a, $b) {
            return strcmp($a['account'], $b['account']);
        });

        if ($format === 'txt') {
            $lines = [];
            foreach ($entries as $e) {
                $lines[] = implode('|', [
                    $e['period'],
                    $e['account'],
                    $e['cuo'],
                    $e['seat'],
                    $e['date'],
                    $e['desc'],
                    number_format($e['debit'], 2, '.', ''),
                    number_format($e['credit'], 2, '.', ''),
                    '1' // Estado
                ]) . '|';
            }
            $content = implode("\r\n", $lines);
            $filename = 'LE' . $ruc . $dateFrom . '0601001111.txt'; // PLE Libro Mayor filename
            return response()->make($content, 200, [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $sheet->setTitle('Libro Mayor');

        $headers = ['Cuenta', 'Periodo', 'CUO', 'Correlativo', 'Fecha', 'Glosa / Descripción', 'Debe', 'Haber', 'Tipo Doc', 'Serie', 'Número'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }

        $row = 2;
        foreach ($entries as $e) {
            $i = 0;
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['account']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['period']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['cuo']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['seat']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['date']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['desc']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['debit']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['credit']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['doc_type']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['serie']);
            $sheet->setCellValue(chr(65 + $i++) . $row, $e['number']);
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Libro_Mayor_' . $ruc . '_' . $dateFrom . '_' . $dateTo . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    // ── Categories CRUD ──

    public function categories()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Categorías';
        $categories = StoreCategory::where('store_id', $store?->id)->orderBy('sort_order')->get();
        return view('seller.categories', compact('pageTitle', 'seller', 'store', 'categories'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $store = $this->store();
        StoreCategory::create(['store_id' => $store->id, 'name' => $request->name, 'sort_order' => $request->sort_order ?? 0, 'status' => 1]);
        return back()->with('success', 'Categoría creada');
    }

    public function categoryUpdate(Request $request, $id)
    {
        $cat = StoreCategory::where('store_id', $this->store()?->id)->findOrFail($id);
        $cat->update($request->only(['name', 'sort_order']));
        $cat->update(['status' => $request->has('status') ? 1 : 0]);
        return back()->with('success', 'Categoría actualizada');
    }

    public function categoryDelete($id)
    {
        StoreCategory::where('store_id', $this->store()?->id)->findOrFail($id)->delete();
        return back()->with('success', 'Categoría eliminada');
    }

    // ── Product CRUD ──

    public function productStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
            'store_category_id' => 'required|exists:store_categories,id',
            'stock_type' => 'nullable|in:packaged,prepared,none',
            'barcode' => 'nullable|string|max:50',
            'tax_type' => 'nullable|in:gravado,exonerado,inafecto',
            'sunat_code' => 'nullable|string|max:8',
        ]);
        $store = $this->store();
        $seller = $this->seller();

        $data = [
            'store_id' => $store->id, 'store_category_id' => $request->store_category_id,
            'name' => $request->name, 'price' => $request->price,
            'barcode' => $request->barcode ?: null,
            'discount_price' => $request->discount_price ?: null,
            'description' => $request->description, 'sort_order' => $request->sort_order ?? 0, 'status' => 1,
            'stock_type' => $request->stock_type ?? 'packaged',
            'tax_type' => $request->tax_type ?? 'gravado',
            'sunat_code' => $request->sunat_code ?: null,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('product', 'public');
        }

        $product = Product::create($data);

        if ($request->has('variations')) {
            $variations = is_array($request->variations) ? $request->variations : json_decode($request->variations, true);
            foreach ($variations as $v) {
                if (!empty($v['name'])) ProductVariation::create(['product_id' => $product->id, 'name' => $v['name'], 'price' => $v['price'] ?? 0, 'status' => 1]);
            }
        }
        if ($request->has('addons')) {
            $addons = is_array($request->addons) ? $request->addons : json_decode($request->addons, true);
            foreach ($addons as $a) {
                if (!empty($a['name'])) ProductAddon::create(['product_id' => $product->id, 'name' => $a['name'], 'price' => $a['price'] ?? 0, 'status' => 1]);
            }
        }

        // Auto-create InvItem + link for packaged products
        if ($product->stock_type === 'packaged') {
            $invItem = \App\Models\InvItem::create([
                'seller_id'  => $seller->id,
                'name'       => $product->name,
                'item_type'  => 'producto',
                'unit'       => $request->input('unit', 'NIU'),
                'tax_type'   => $request->input('tax_type', 'gravado'),
                'cost'       => $request->input('cost', 0),
                'sale_price' => $product->price,
                'stock'      => $request->input('initial_stock', 0),
                'min_stock'  => $request->input('min_stock', 5),
                'sunat_code' => $request->input('sunat_code'),
            ]);

            \App\Models\InvProductItem::create([
                'product_id' => $product->id,
                'item_id'    => $invItem->id,
                'quantity'   => 1,
            ]);

            // Register initial stock via kardex if provided
            if ($request->input('initial_stock', 0) > 0) {
                $warehouseId = \App\Models\InvWarehouse::where('seller_id', $seller->id)->where('is_default', true)->value('id')
                    ?? \App\Models\InvWarehouse::where('seller_id', $seller->id)->value('id');

                \App\Services\KardexService::entry(
                    $seller->id, $invItem->id, $request->input('initial_stock'), $invItem->cost ?? 0, $request->input('initial_stock'),
                    'Stock inicial auto-creado', null, null, $warehouseId
                );

                if ($warehouseId) {
                    $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $invItem->id],
                        ['stock' => 0]
                    );
                    $wStock->increment('stock', $request->input('initial_stock'));
                }
            }
        }

        return back()->with('success', 'Producto creado: ' . $product->name);
    }

    public function productUpdate(Request $request, $id)
    {
        $store = $this->store();
        $seller = $this->seller();
        $product = Product::where('store_id', $store->id)->findOrFail($id);
        $product->update($request->only(['name', 'price', 'barcode', 'discount_price', 'description', 'store_category_id', 'sort_order', 'stock_type', 'tax_type', 'sunat_code']));
        $product->update(['status' => $request->has('status') ? 1 : 0]);

        // Sync with linked inventory item if type is packaged
        if ($product->stock_type === 'packaged') {
            $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
            if (!$link) {
                // Auto-create InvItem + link
                $invItem = \App\Models\InvItem::create([
                    'seller_id'  => $seller->id,
                    'name'       => $product->name,
                    'item_type'  => 'producto',
                    'unit'       => $request->input('unit', 'NIU'),
                    'tax_type'   => $request->input('tax_type', 'gravado'),
                    'cost'       => $request->input('cost', 0),
                    'sale_price' => $product->price,
                    'stock'      => $request->input('initial_stock', 0),
                    'min_stock'  => $request->input('min_stock', 5),
                    'sunat_code' => $request->input('sunat_code'),
                ]);

                \App\Models\InvProductItem::create([
                    'product_id' => $product->id,
                    'item_id'    => $invItem->id,
                    'quantity'   => 1,
                ]);

                if ($request->input('initial_stock', 0) > 0) {
                    $warehouseId = \App\Models\InvWarehouse::where('seller_id', $seller->id)->where('is_default', true)->value('id')
                        ?? \App\Models\InvWarehouse::where('seller_id', $seller->id)->value('id');

                    \App\Services\KardexService::entry(
                        $seller->id, $invItem->id, $request->input('initial_stock'), $invItem->cost ?? 0, $request->input('initial_stock'),
                        'Stock inicial auto-creado en actualización', null, null, $warehouseId
                    );

                    if ($warehouseId) {
                        $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'item_id' => $invItem->id],
                            ['stock' => 0]
                        );
                        $wStock->increment('stock', $request->input('initial_stock'));
                    }
                }
            } else {
                $item = $link->item;
                if ($item) {
                    $item->update([
                        'name'       => $product->name,
                        'unit'       => $request->input('unit', 'NIU'),
                        'tax_type'   => $request->input('tax_type', 'gravado'),
                        'cost'       => $request->input('cost', 0),
                        'sale_price' => $product->price,
                        'min_stock'  => $request->input('min_stock', 5),
                        'sunat_code' => $request->input('sunat_code'),
                    ]);
                }
            }
        }

        if ($request->hasFile('image')) {
            $product->update(['image' => $request->file('image')->store('product', 'public')]);
        }

        // Replace variations
        $product->variations()->delete();
        if ($request->has('variations')) {
            $variations = is_array($request->variations) ? $request->variations : json_decode($request->variations, true);
            foreach ($variations as $v) {
                if (!empty($v['name'])) ProductVariation::create(['product_id' => $product->id, 'name' => $v['name'], 'price' => $v['price'] ?? 0, 'status' => 1]);
            }
        }
        // Replace addons
        $product->addons()->delete();
        if ($request->has('addons')) {
            $addons = is_array($request->addons) ? $request->addons : json_decode($request->addons, true);
            foreach ($addons as $a) {
                if (!empty($a['name'])) ProductAddon::create(['product_id' => $product->id, 'name' => $a['name'], 'price' => $a['price'] ?? 0, 'status' => 1]);
            }
        }

        return back()->with('success', 'Producto actualizado');
    }

    public function productDelete($id)
    {
        Product::where('store_id', $this->store()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Producto eliminado');
    }

    public function productAdjustStock(Request $request, $id)
    {
        $request->validate([
            'adjust_type' => 'required|in:in,out',
            'adjust_qty' => 'required|numeric|min:0.01',
            'adjust_reason' => 'required|string|max:250',
            'adjust_warehouse_id' => 'required|exists:inv_warehouses,id',
        ]);

        $store = $this->store();
        $seller = $this->seller();
        $product = Product::where('store_id', $store->id)->findOrFail($id);

        $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
        if (!$link || !$link->item) {
            // Productos devolubles creados antes del módulo de inventario no
            // tienen vínculo aún: créalo al primer ajuste de Kardex.
            if (!$product->isStockPackaged()) {
                return back()->with('error', 'Solo los productos devolubles pueden manejar stock en Kardex.');
            }

            $item = \App\Models\InvItem::create([
                'seller_id'  => $seller->id,
                'name'       => $product->name,
                'item_type'  => \App\Models\InvItem::TYPE_PRODUCTO,
                'unit'       => 'NIU',
                'tax_type'   => $product->tax_type ?? 'gravado',
                'cost'       => 0,
                'sale_price' => $product->price,
                'stock'      => 0,
                'min_stock'  => 5,
                'sunat_code' => $product->sunat_code,
            ]);
            $link = \App\Models\InvProductItem::create([
                'product_id' => $product->id,
                'item_id'    => $item->id,
                'quantity'   => 1,
            ]);
        }

        $item = $link->item;
        $qty = floatval($request->adjust_qty);
        $adjType = $request->adjust_type;
        $reason = $request->adjust_reason;
        $warehouseId = $request->adjust_warehouse_id;
        if (!\App\Models\InvWarehouse::where('seller_id', $seller->id)->whereKey($warehouseId)->exists()) {
            return back()->with('error', 'El almacén seleccionado no pertenece a tu negocio.');
        }

        $oldStock = $item->stock;
        if ($adjType === 'in') {
            $newStock = $oldStock + $qty;
            $item->update(['stock' => $newStock]);

            $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                ['stock' => 0]
            );
            $wStock->increment('stock', $qty);

            \App\Services\KardexService::entry(
                $seller->id, $item->id, $qty, $item->cost ?? 0, $newStock,
                $reason, 'manual_entry', null, $warehouseId
            );
        } else {
            $newStock = $oldStock - $qty;
            $item->update(['stock' => $newStock]);

            $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                ['stock' => 0]
            );
            $wStock->decrement('stock', $qty);

            \App\Services\KardexService::exit(
                $seller->id, $item->id, $qty, $item->cost ?? 0, $newStock,
                $reason, 'manual_exit', null, $warehouseId
            );
        }

        return back()->with('success', 'Ajuste de inventario aplicado con éxito para: ' . $product->name);
    }

    // ── OCR Product Parsing ──

    public function ocrParse(Request $request)
    {
        $request->validate(['image' => 'required|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,xlsx,xls,csv']);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $extractedText = '';
        $method = 'manual';
        $items = [];

        // Excel/CSV → parsear columnas directo
        if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $method = 'excel';
            $items = $this->parseExcelColumns($file->getPathname(), $ext);
        }
        // PDF → extraer texto
        elseif ($ext === 'pdf') {
            $method = 'pdf';
            $extractedText = $this->extractPdfText($file->getPathname());
            $items = $this->parseMenuText($extractedText);
        }
        // Imagen → OCR
        else {
            $imageBase64 = base64_encode(file_get_contents($file->getPathname()));
            $visionKey = gs('google_vision_api_key');
            if ($visionKey) {
                $method = 'google_vision';
                $extractedText = $this->googleVisionOcr($imageBase64, $visionKey);
            }
            if (empty(trim($extractedText))) {
                $method = 'local_parser';
                $extractedText = $this->localOcrFallback($file->getPathname());
            }
            $items = $this->parseMenuText($extractedText);
        }

        return response()->json([
            'status' => 'success',
            'raw_text' => $extractedText,
            'items' => $items,
            'method' => $method,
        ]);
    }

    private function parseExcelColumns($path, $ext)
    {
        $items = [];
        try {
            if ($ext === 'csv') {
                $rows = array_map('str_getcsv', file($path));
            } else {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            }

            // Skip header row if it looks like a header
            $startRow = 0;
            if (count($rows) > 0) {
                $first = array_map('trim', $rows[0]);
                $isHeader = stripos($first[0] ?? '', 'categor') !== false
                    || stripos($first[1] ?? '', 'nombre') !== false
                    || stripos($first[2] ?? '', 'descrip') !== false;
                if ($isHeader) $startRow = 1;
            }
            $currentCategory = 'General';
            for ($i = $startRow; $i < count($rows); $i++) {
                $row = array_map('trim', $rows[$i]);
                $colA = $row[0] ?? '';
                $colB = $row[1] ?? '';
                $colC = $row[2] ?? '';
                $colD = $row[3] ?? '';
                $colE = $row[4] ?? '';

                // Skip completely empty rows
                if (empty($colA) && empty($colB) && empty($colC) && empty($colD)) continue;

                // If colB is empty but colA has text → it's a category header
                if (!empty($colA) && empty($colB) && empty($colC) && empty($colD)) {
                    $currentCategory = trim($colA);
                    continue;
                }

                // ColA might be a category if colB has a name and colD has a price
                $cat = !empty($colA) ? trim($colA) : $currentCategory;
                $name = trim($colB);
                $desc = trim($colC);
                $price = floatval(str_replace([',', 'S/', 'S/ ', ' '], ['', '', '', '.'], $colD));
                $taxType = in_array(strtolower($colE), ['gravado', 'exonerado', 'inafecto']) ? strtolower($colE) : 'gravado';

                // If colA has text and colB has name, update currentCategory
                if (!empty($colA) && !empty($colB)) {
                    $currentCategory = $cat;
                }

                if (!empty($name) && $price > 0) {
                    $items[] = [
                        'category' => $currentCategory,
                        'name' => $name,
                        'description' => $desc,
                        'price' => $price,
                        'tax_type' => $taxType,
                    ];

                }
                // Row without price but with name → might be category + name only (next rows share category)
                elseif (!empty($name) && empty($colD) && !empty($colA)) {
                    $currentCategory = trim($colA);
                    if (!empty($colB)) {
                        $items[] = [
                            'category' => $currentCategory,
                            'name' => $name,
                            'description' => $desc,
                            'price' => 0,
                            'tax_type' => $taxType,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {}

        return $items;
    }

    private function extractPdfText($path)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        } catch (\Exception $e) {
            return '';
        }
    }

    private function googleVisionOcr($imageBase64, $apiKey)
    {
        try {
            $payload = json_encode([
                'requests' => [[
                    'image' => ['content' => $imageBase64],
                    'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]],
                ]]
            ]);

            $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            return $result['responses'][0]['textAnnotations'][0]['description'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    private function localOcrFallback($imagePath)
    {
        // Intentar con Tesseract si está instalado en el servidor
        $tesseract = trim(shell_exec('which tesseract 2>/dev/null'));
        if ($tesseract) {
            $output = shell_exec(escapeshellcmd("tesseract $imagePath stdout -l spa 2>/dev/null"));
            if ($output) return trim($output);
        }

        // Sin OCR disponible: devolver texto vacío
        return '';
    }

    private function parseMenuText($text)
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $items = [];
        $currentCategory = 'General';

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            if (empty($line)) continue;

            // Detectar precio al final: S/ XX.XX o XX.XX o S/XX
            $hasPrice = preg_match('/(?:S\/\s*)?(\d+[.,]\d{2}|\d+)\s*$/', $line, $priceMatch);
            $price = $hasPrice ? (float) str_replace(',', '.', $priceMatch[1]) : 0;

            // Detectar si es categoría: sin precio, texto corto, puede ser mayúsculas
            $cleanLine = preg_replace('/[^\w\sáéíóúñÁÉÍÓÚÑ]/u', '', $line);
            $wordCount = str_word_count($cleanLine);
            $isAllCaps = strtoupper($cleanLine) === $cleanLine && strlen($cleanLine) > 2 && strlen($cleanLine) < 45;
            $isShortNoNum = $wordCount <= 3 && strlen($cleanLine) < 45 && !preg_match('/\d{4,}/', $line) && !$hasPrice;

            if ($isAllCaps || ($isShortNoNum && !$hasPrice)) {
                $currentCategory = ucfirst(mb_strtolower($cleanLine, 'UTF-8'));
                continue;
            }

            // Es un item con precio
            if ($hasPrice && $price > 0) {
                // Extraer el nombre (todo antes del precio)
                $name = trim(preg_replace('/\s+(?:S\/\s*)?\d+[.,]\d{0,2}\s*$/', '', $line));

                // Separar nombre muy largo en nombre + descripción
                $desc = '';
                $nameWords = explode(' ', $name);
                if (count($nameWords) > 5 && strlen($name) > 35) {
                    $name = implode(' ', array_slice($nameWords, 0, 4));
                    $desc = implode(' ', array_slice($nameWords, 4));
                }

                if (!empty($name)) {
                    $items[] = [
                        'category' => $currentCategory,
                        'name' => $name,
                        'description' => $desc,
                        'price' => $price,
                    ];
                }
            }
        }

        return $items;
    }

    // ── Bulk Product Upload ──

    public function bulkUpload()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Carga Masiva de Productos';
        return view('seller.bulk', compact('pageTitle', 'seller', 'store'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Categoría');
        $sheet->setCellValue('B1', 'Nombre del Producto');
        $sheet->setCellValue('C1', 'Descripción');
        $sheet->setCellValue('D1', 'Precio (S/)');
        $sheet->setCellValue('E1', 'Tipo Tributario');

        // Style headers
        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '16A34A']]];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Tax type validation (data validation)
        $validation = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setFormula1('"gravado,exonerado,inafecto"');
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Tipo inválido');
        $validation->setError('Selecciona: gravado, exonerado o inafecto');
        $sheet->setDataValidation('E2:E1000', $validation);

        // Sample data
        $samples = [
            ['ENTRADAS', 'Piqueo charapita', '6 canastitas con chorizo, yuca y salsa', 17.00, 'gravado'],
            ['ENTRADAS', 'Ensalada de la casa', 'Mix de lechugas, palta, tomate cherry', 12.00, 'gravado'],
            ['PLATOS DE FONDO', 'Juane de gallina', 'Arroz con gallina envuelto en bijao', 15.00, 'gravado'],
            ['PLATOS DE FONDO', 'Tacacho con cecina', 'Plátano asado con cecina de cerdo', 18.00, 'gravado'],
            ['BEBIDAS', 'Limonada frozen', 'Limonada con hielo frappé', 8.00, 'gravado'],
            ['BEBIDAS', 'Jugo de cocona', 'Jugo natural de cocona', 7.00, 'gravado'],
            ['POSTRES', 'Mousse de maracuyá', 'Postre cremoso de maracuyá', 10.00, 'gravado'],
        ];

        $row = 2;
        foreach ($samples as $s) {
            $sheet->setCellValue('A' . $row, $s[0]);
            $sheet->setCellValue('B' . $row, $s[1]);
            $sheet->setCellValue('C' . $row, $s[2]);
            $sheet->setCellValue('D' . $row, $s[3]);
            $sheet->setCellValue('E' . $row, $s[4]);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(18);

        // Add instructions sheet
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');
        $sheet2->setCellValue('A1', 'INSTRUCCIONES PARA LA PLANTILLA');
        $sheet2->setCellValue('A3', '1. La columna "Categoría" agrupa productos. Si la categoría ya existe en tu tienda, se reutiliza.');
        $sheet2->setCellValue('A4', '2. El "Nombre del Producto" es obligatorio.');
        $sheet2->setCellValue('A5', '3. La "Descripción" es opcional.');
        $sheet2->setCellValue('A6', '4. El "Precio" debe ser un número (ej: 17.00).');
        $sheet2->setCellValue('A7', '5. "Tipo Tributario" define la afectación al IGV:');
        $sheet2->setCellValue('A8', '   • gravado → Gravado con IGV 18% (por defecto si se deja vacío)');
        $sheet2->setCellValue('A9', '   • exonerado → Exonerado de IGV');
        $sheet2->setCellValue('A10', '   • inafecto → Inafecto (no sujeto a IGV)');
        $sheet2->setCellValue('A12', '6. Puedes agregar tantas filas como necesites. Elimina los datos de ejemplo.');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getColumnDimension('A')->setWidth(80);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'plantilla-productos-liztogo.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function bulkStore(Request $request)
    {
        $store = $this->store();
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'No tienes una tienda creada. Crea una primero.']);
        }

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
            $taxType = in_array($item['tax_type'] ?? '', ['gravado', 'exonerado', 'inafecto']) ? $item['tax_type'] : 'gravado';

            if (empty($name) || $price <= 0) continue;

            // Buscar o crear categoría
            $catKey = mb_strtolower(trim($catName), 'UTF-8');

            if (!isset($categoryMap[$catKey])) {
                // Buscar existente (case-insensitive)
                $existingCat = StoreCategory::where('store_id', $store->id)
                    ->whereRaw('LOWER(name) = ?', [$catKey])
                    ->first();

                if ($existingCat) {
                    $categoryMap[$catKey] = $existingCat->id;
                    $catName = $existingCat->name;
                } else {
                    try {
                        $cat = StoreCategory::create([
                            'store_id' => $store->id,
                            'name' => $catName,
                            'sort_order' => count($categoryMap),
                            'status' => 1,
                        ]);
                        $categoryMap[$catKey] = $cat->id;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (!isset($details[$catName])) {
                    $details[$catName] = ['category' => $catName, 'count' => 0, 'items' => []];
                }
            }

            // Saltar duplicados
            $exists = Product::where('store_id', $store->id)
                ->where('store_category_id', $categoryMap[$catKey])
                ->where('name', $name)
                ->exists();
            if ($exists) continue;

            try {
                Product::create([
                    'store_id' => $store->id,
                    'store_category_id' => $categoryMap[$catKey],
                    'name' => $name,
                    'description' => $desc,
                    'price' => $price,
                    'tax_type' => $taxType,
                    'sort_order' => $details[$catName]['count'],
                    'status' => 1,
                ]);
                $details[$catName]['count']++;
                $details[$catName]['items'][] = ['name' => $name, 'price' => $price];
                $created++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json([
            'status' => 'success',
            'created' => $created,
            'categories' => count($categoryMap),
            'details' => array_values($details),
        ]);
    }

    // ── Delivery Apps ──

    public function delivery()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Delivery Apps';
        $storeId = $store?->id;

        $delOrders = DeliveryOrder::where('store_id', $storeId)->with('driver', 'user', 'items')->latest()->paginate(15);

        $apps = [
            ['name' => 'Lizto', 'icon' => '🏍️', 'active' => true, 'orders' => $delOrders->where('payment_method_code', '0')->count()],
            ['name' => 'Didi Food', 'icon' => '🛵', 'active' => false, 'coming' => true],
            ['name' => 'Rappi', 'icon' => '📦', 'active' => false, 'coming' => true],
            ['name' => 'PedidosYa', 'icon' => '🛒', 'active' => false, 'coming' => true],
            ['name' => 'Llama Food', 'icon' => '🦙', 'active' => false, 'coming' => true],
            ['name' => 'Daz', 'icon' => '🚀', 'active' => false, 'coming' => true],
        ];

        return view('seller.delivery', compact('pageTitle', 'seller', 'store', 'delOrders', 'apps'));
    }

    // ── Helper to fetch the Active Company of the Seller ──
    private function activeCompany()
    {
        $seller = $this->seller();
        $companyId = Session::get('active_company_id');
        
        // Auto-migrate if seller already has RUC registered in sellers table but no companies exist yet
        if ($seller->document_number && !\App\Models\SellerCompany::where('seller_id', $seller->id)->exists()) {
            $comp = \App\Models\SellerCompany::create([
                'seller_id'       => $seller->id,
                'document_number' => $seller->document_number,
                'business_name'   => $seller->business_name ?? $seller->name,
                'trade_name'      => $seller->trade_name,
                'ubigeo'          => $seller->ubigeo,
                'address'         => $seller->address,
                'sunat_sol_user'  => $seller->sunat_sol_user,
                'sunat_sol_pass'  => $seller->sunat_sol_pass,
                'sunat_env'       => $seller->sunat_env ?? 'beta',
                'sunat_cert_path' => $seller->sunat_cert_path,
                'sunat_cert_pass' => $seller->sunat_cert_pass,
                'is_active'       => true
            ]);
            Session::put('active_company_id', $comp->id);

            // Relate existing types and series to this new company
            PosInvoiceType::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            PosInvoiceSeries::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            \App\Models\SunatInvoice::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            
            // Auto-seed default invoice types for this new company if they don't exist yet
            if (!PosInvoiceType::where('seller_company_id', $comp->id)->exists()) {
                $defaults = [
                    ['code' => '01', 'name' => 'Factura Electrónica', 'sunat_code' => '01', 'is_electronic' => true],
                    ['code' => '03', 'name' => 'Boleta de Venta Electrónica', 'sunat_code' => '03', 'is_electronic' => true],
                    ['code' => '07', 'name' => 'Nota de Crédito Electrónica', 'sunat_code' => '07', 'is_electronic' => true],
                    ['code' => '08', 'name' => 'Nota de Débito Electrónica', 'sunat_code' => '08', 'is_electronic' => true],
                    ['code' => 'NV', 'name' => 'Nota de Venta', 'sunat_code' => null, 'is_electronic' => false],
                ];
                foreach ($defaults as $d) {
                    PosInvoiceType::create(array_merge($d, [
                        'seller_id'         => $seller->id,
                        'seller_company_id' => $comp->id
                    ]));
                }
            }

            return $comp;
        }

        $company = null;
        if ($companyId) {
            $company = \App\Models\SellerCompany::where('seller_id', $seller->id)->find($companyId);
        }

        if (!$company) {
            $company = \App\Models\SellerCompany::where('seller_id', $seller->id)->orderBy('is_active', 'desc')->first();
            if ($company) {
                Session::put('active_company_id', $company->id);
            }
        }

        return $company;
    }

    // ── Switch Active Company ──
    public function switchCompany($id)
    {
        $seller = $this->seller();
        $company = \App\Models\SellerCompany::where('seller_id', $seller->id)->findOrFail($id);
        
        \App\Models\SellerCompany::where('seller_id', $seller->id)->update(['is_active' => false]);
        $company->update(['is_active' => true]);
        
        Session::put('active_company_id', $company->id);
        return back()->with('success', 'Trabajando con: ' . $company->business_name);
    }

    // ── Create a new Company ──
    public function companyStore(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        $companyCount = \App\Models\SellerCompany::where('seller_id', $seller->id)->count();

        // Check active package limit for companies
        $activePackage = \App\Models\StorePackage::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->with('package')
            ->first();

        $maxCompanies = 1;
        if ($activePackage && $activePackage->package) {
            $features = (array) ($activePackage->package->features ?? []);
            foreach ($features as $f) {
                $fObj = (object) $f;
                if (isset($fObj->key) && $fObj->key === 'companies') {
                    $val = strtolower(trim($fObj->value));
                    if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                        $maxCompanies = 999999;
                    } elseif (is_numeric($val)) {
                        $maxCompanies = (int) $val;
                    }
                    break;
                }
            }
        }

        if ($companyCount >= $maxCompanies) {
            $limitLabel = $maxCompanies === 999999 ? 'Ilimitado' : $maxCompanies;
            return back()->with('error', "Tu plan permite un máximo de {$limitLabel} empresa(s). Actualiza tu plan para registrar y gestionar múltiples empresas.");
        }

        $request->validate([
            'document_number'   => 'required|string|size:11',
            'business_name'     => 'required|string|max:255',
            'trade_name'        => 'nullable|string|max:255',
            'ubigeo'            => 'nullable|string|max:6',
            'address'           => 'nullable|string|max:500',
            'business_address'  => 'nullable|string|max:500',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'department'        => 'nullable|string|max:100',
            'province'          => 'nullable|string|max:100',
            'district'          => 'nullable|string|max:100',
            'default_tax_type'  => 'nullable|string|in:gravado,exonerado,inafecto',
        ]);

        $company = \App\Models\SellerCompany::create([
            'seller_id'         => $seller->id,
            'document_number'   => $request->document_number,
            'business_name'     => $request->business_name,
            'trade_name'        => $request->trade_name,
            'ubigeo'            => $request->ubigeo,
            'address'           => $request->address,
            'business_address'  => $request->business_address,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'department'        => $request->department,
            'province'          => $request->province,
            'district'          => $request->district,
            'default_tax_type'  => $request->default_tax_type ?? SellerCompany::TAX_GRAVADO,
            'is_active'         => false,
        ]);

        // Auto-seed default invoice types for this new company
        $defaults = [
            ['code' => '01', 'name' => 'Factura Electrónica', 'sunat_code' => '01', 'is_electronic' => true],
            ['code' => '03', 'name' => 'Boleta de Venta Electrónica', 'sunat_code' => '03', 'is_electronic' => true],
            ['code' => '07', 'name' => 'Nota de Crédito Electrónica', 'sunat_code' => '07', 'is_electronic' => true],
            ['code' => '08', 'name' => 'Nota de Débito Electrónica', 'sunat_code' => '08', 'is_electronic' => true],
            ['code' => 'NV', 'name' => 'Nota de Venta', 'sunat_code' => null, 'is_electronic' => false],
        ];
        foreach ($defaults as $d) {
            PosInvoiceType::create(array_merge($d, [
                'seller_id'         => $seller->id,
                'seller_company_id' => $company->id
            ]));
        }

        // Set as active if it's the first one
        if (\App\Models\SellerCompany::where('seller_id', $seller->id)->count() === 1) {
            $company->update(['is_active' => true]);
            Session::put('active_company_id', $company->id);
        }

        return back()->with('success', 'Empresa "' . $company->business_name . '" registrada exitosamente.');
    }

    // ── Update Company Data ──
    public function companyUpdate(Request $request, $id)
    {
        $seller = $this->seller();
        $company = \App\Models\SellerCompany::where('seller_id', $seller->id)->findOrFail($id);

        $request->validate([
            'document_number'   => 'required|string|size:11',
            'business_name'     => 'required|string|max:255',
            'trade_name'        => 'nullable|string|max:255',
            'ubigeo'            => 'nullable|string|max:6',
            'address'           => 'nullable|string|max:500',
            'business_address'  => 'nullable|string|max:500',
            'latitude'          => 'nullable|numeric|between:-90,90',
            'longitude'         => 'nullable|numeric|between:-180,180',
            'department'        => 'nullable|string|max:100',
            'province'          => 'nullable|string|max:100',
            'district'          => 'nullable|string|max:100',
            'default_tax_type'  => 'nullable|string|in:gravado,exonerado,inafecto',
        ]);

        $company->update([
            'document_number'   => $request->document_number,
            'business_name'     => $request->business_name,
            'trade_name'        => $request->trade_name,
            'ubigeo'            => $request->ubigeo,
            'address'           => $request->address,
            'business_address'  => $request->business_address,
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'department'        => $request->department,
            'province'          => $request->province,
            'district'          => $request->district,
            'default_tax_type'  => $request->default_tax_type ?? SellerCompany::TAX_GRAVADO,
        ]);

        return back()->with('success', 'Datos de la empresa actualizados.');
    }

    // ── Delete Company ──
    public function companyDelete($id)
    {
        $seller = $this->seller();
        $company = \App\Models\SellerCompany::where('seller_id', $seller->id)->findOrFail($id);

        // Check if there are emitted invoices
        if (\App\Models\SunatInvoice::where('seller_company_id', $company->id)->exists()) {
            return back()->with('error', 'No se puede eliminar la empresa porque tiene comprobantes emitidos.');
        }

        $company->delete();

        // Clear active session if deleted the active one
        if (Session::get('active_company_id') == $id) {
            Session::forget('active_company_id');
        }

        return back()->with('success', 'Empresa eliminada.');
    }

    // ── Logo Update ──

    public function logoUpdate(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró la tienda activa');
        }

        if ($request->hasFile('logo')) {
            try {
                $old = $store->image;
                $store->image = fileUploader($request->file('logo'), 'assets/images/store', null, $old);
                $store->save();
                return back()->with('success', 'Logo de la tienda actualizado con éxito');
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir el logo: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'No se seleccionó ninguna imagen');
    }

    public function coverVideoUpdate(Request $request)
    {
        $request->validate([
            'cover_video' => 'required|mimes:mp4,webm,ogg,mov,avi|max:20480',
        ]);

        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró la tienda activa');
        }

        if (!$store->hasPremiumPackage()) {
            return back()->with('error', 'La función de video de portada solo está disponible en el Plan Premium.');
        }

        if ($request->hasFile('cover_video')) {
            try {
                $old = $store->cover_video;
                $store->cover_video = fileUploader($request->file('cover_video'), 'assets/video/store_cover', null, $old);
                $store->save();
                return back()->with('success', 'Video de portada actualizado con éxito');
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir el video: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'No se seleccionó ningún archivo de video');
    }

    // ── Invoice Configuration ──

    public function invoicing(Request $request)
    {
        $seller = $this->seller(); 
        $store = Store::where('seller_id', $seller->id)->with('seller')->first();
        $pageTitle = 'Facturación Electrónica';

        $companies = \App\Models\SellerCompany::where('seller_id', $seller->id)->get();
        $activeCompany = $this->activeCompany();

        $types = collect();
        $sunatInvoices = collect();

        if ($activeCompany) {
            $types = PosInvoiceType::where('seller_company_id', $activeCompany->id)->with('series')->get();
            $query = \App\Models\SunatInvoice::where(function($q) use ($activeCompany, $seller) {
                $q->where('seller_company_id', $activeCompany->id)
                  ->orWhere(function($sq) use ($seller) {
                      $sq->where('seller_id', $seller->id)->whereNull('seller_company_id');
                  });
            });

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function($q) use ($search) {
                    $q->where('serie', 'LIKE', "%{$search}%")
                      ->orWhere('correlativo', 'LIKE', "%{$search}%")
                      ->orWhere('cliente_nombre', 'LIKE', "%{$search}%")
                      ->orWhere('cliente_num_doc', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('tipo_doc')) {
                $query->where('tipo_doc', $request->tipo_doc);
            }

            if ($request->filled('cdr_status')) {
                $query->where('cdr_status', $request->cdr_status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('fecha_emision', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('fecha_emision', '<=', $request->date_to);
            }

            $sunatInvoices = $query->latest('id')->paginate(15)->appends($request->all());
        }

        return view('seller.invoicing', compact('pageTitle', 'seller', 'store', 'companies', 'activeCompany', 'types', 'sunatInvoices'));
    }

    public function sunatConfig(Request $request)
    {
        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'Registra primero una empresa.');
        }

        $request->validate([
            'sunat_sol_user' => 'required|string',
            'sunat_sol_pass' => 'required|string',
            'sunat_env'      => 'required|in:beta,production',
        ]);

        $data = $request->only(['sunat_sol_user', 'sunat_sol_pass', 'sunat_env']);

        if ($request->hasFile('sunat_cert')) {
            $path = $request->file('sunat_cert')->store('sunat_certs', 'local');
            $data['sunat_cert_path'] = $path;
        }
        if ($request->filled('sunat_cert_pass')) {
            $data['sunat_cert_pass'] = $request->sunat_cert_pass;
        }

        $activeCompany->update($data);
        return back()->with('success', 'Configuración SUNAT de la empresa activa actualizada');
    }

    public function generateInvoice(Request $request, $id)
    {
        $seller = $this->seller();
        $activeCompany = $this->activeCompany();
        
        if (!$activeCompany) {
            return back()->with('error', 'Debes configurar una empresa antes de facturar.');
        }

        $request->validate([
            'series_id' => 'required',
            'payment_method' => 'nullable|string',
            'payments' => 'nullable|array',
        ]);

        $order = PosOrder::where('seller_id', $seller->id)->with('items')->findOrFail($id);
        $series = PosInvoiceSeries::where('id', $request->series_id)
            ->where('seller_company_id', $activeCompany->id)
            ->with('invoiceType')->firstOrFail();

        $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

        if ($tipoDoc !== 'NV' && $store && $store->hasReachedInvoiceLimit()) {
            $planName = $store->activePackagesRelation()->first()?->package?->name ?? 'Actual';
            $limit = $store->getInvoiceLimit() ?? 50;
            return back()->with('error', "Has alcanzado el límite de {$limit} comprobantes electrónicos de tu plan {$planName}. Actualiza a un plan superior para facturación ilimitada.");
        }

        $paymentMethod = $request->payment_method ?? 'cash';
        $paymentDetails = null;

        if ($request->has('payments') && is_array($request->payments)) {
            $splits = [];
            foreach ($request->payments as $method => $amount) {
                if ($amount > 0) {
                    $splits[$method] = round((float) $amount, 2);
                }
            }
            if (count($splits) > 0) {
                $splitTotal = round(array_sum($splits), 2);
                $orderTotal = round((float) $order->total, 2);
                if (abs($splitTotal - $orderTotal) > 0.01) {
                    return back()->with('error', 'El monto total ingresado debe coincidir con el total de la cuenta.')->withInput();
                }
                $paymentDetails = $splits;
                if (count($splits) > 1) {
                    $paymentMethod = 'split';
                } else {
                    $paymentMethod = array_key_first($splits);
                }
            }
        }

        try {
            $sunatService = new \App\Services\SunatService($activeCompany);

            $clientData = [
                'tipo_doc' => $request->tipo_doc ?? '6',
                'num_doc'  => $request->num_doc ?? ($order->customer_doc ?? '0'),
                'nombre'   => $order->customer_name ?? 'CLIENTE VARIOS',
            ];

            $sunatInvoice = $sunatService->sendInvoice($order, $series, $clientData);

            if ($sunatInvoice->cdr_status === \App\Models\SunatInvoice::STATUS_ACCEPTED) {
                $number = str_pad($sunatInvoice->correlativo, 8, '0', STR_PAD_LEFT);
                $order->update([
                    'invoice_type_id'   => $series->invoiceType->id,
                    'invoice_series'    => $sunatInvoice->serie,
                    'invoice_number'    => $number,
                    'payment_status'    => 'paid',
                    'paid_at'           => now(),
                    'payment_method'    => $paymentMethod,
                    'payment_details'   => $paymentDetails,
                ]);
                return back()->with('success', 'Comprobante ' . $sunatInvoice->serie . '-' . $number . ' emitido y aceptado por SUNAT ✓');
            } else {
                return back()->with('error', 'SUNAT rechazó el comprobante: ' . ($sunatInvoice->cdr_response ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            // Fallback: emitir sin SUNAT (solo incrementar correlativo)
            $number = str_pad($series->current_number, 8, '0', STR_PAD_LEFT);
            $order->update([
                'invoice_type_id'   => $series->invoiceType->id,
                'invoice_series'    => $series->series,
                'invoice_number'    => $number,
                'payment_status'    => 'paid',
                'paid_at'           => now(),
                'payment_method'    => $paymentMethod,
                'payment_details'   => $paymentDetails,
            ]);
            $series->increment('current_number');
            return back()->with('warning', 'Comprobante ' . $series->series . '-' . $number . ' emitido localmente. SUNAT: ' . $e->getMessage());
        }
    }

    public function invoiceSeriesStore(Request $request)
    {
        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'No hay una empresa activa seleccionada.');
        }

        $type = PosInvoiceType::where('seller_company_id', $activeCompany->id)->findOrFail($request->invoice_type_id);
        PosInvoiceSeries::create([
            'invoice_type_id'   => $type->id,
            'seller_id'         => $this->seller()->id,
            'seller_company_id' => $activeCompany->id,
            'series'            => strtoupper($request->series),
            'current_number'    => $request->current_number ?? 1,
            'max_number'        => $request->max_number ?? 99999999,
            'active'            => true,
        ]);
        return back()->with('success', 'Serie ' . strtoupper($request->series) . ' agregada');
    }

    public function invoiceSeriesDelete($id)
    {
        $activeCompany = $this->activeCompany();
        PosInvoiceSeries::where('seller_company_id', $activeCompany->id)->findOrFail($id)->delete();
        return back()->with('success', 'Serie eliminada');
    }

    public function invoiceTypeStore(Request $request)
    {
        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'No hay una empresa activa seleccionada.');
        }

        $request->validate([
            'code'          => 'required|string|max:10',
            'name'          => 'required|string|max:255',
            'sunat_code'    => 'nullable|string|max:10',
            'is_electronic' => 'required|boolean',
        ]);

        PosInvoiceType::create([
            'seller_id'         => $this->seller()->id,
            'seller_company_id' => $activeCompany->id,
            'code'              => strtoupper($request->code),
            'name'              => $request->name,
            'sunat_code'        => $request->sunat_code,
            'is_electronic'     => (bool) $request->is_electronic,
            'active'            => true,
        ]);

        return back()->with('success', 'Tipo de comprobante "' . $request->name . '" registrado exitosamente.');
    }

    public function invoiceTypeDelete($id)
    {
        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'No hay una empresa activa seleccionada.');
        }

        $type = PosInvoiceType::where('seller_company_id', $activeCompany->id)
            ->whereNotIn('code', ['01', '03', '07', '08', 'NV']) // Protect defaults
            ->findOrFail($id);

        $type->series()->delete();
        $type->delete();

        return back()->with('success', 'Tipo de comprobante eliminado');
    }

    // ── RUC Registration for Seller ──

    public function registerRuc(Request $request)
    {
        // Wrapper forwarder to companyStore
        return $this->companyStore($request);
    }

    // ── Cash Register ──
    
    protected function getActiveCashSession($sellerId)
    {
        $staffId = session()->get('seller_staff_id') ?? session()->get('pos_staff_id');
        if ($staffId) {
            $staff = \App\Models\PosStaff::find($staffId);
            if ($staff && $staff->pos_register_id) {
                $session = PosCashSession::where('seller_id', $sellerId)
                    ->where('pos_register_id', $staff->pos_register_id)
                    ->open()
                    ->first();
                if ($session) {
                    return $session;
                }
            }
        }
        return PosCashSession::where('seller_id', $sellerId)->open()->first();
    }

    protected function isAnyCashOpen($sellerId)
    {
        $staffId = session()->get('seller_staff_id') ?? session()->get('pos_staff_id');
        if ($staffId) {
            $staff = \App\Models\PosStaff::find($staffId);
            if ($staff && $staff->pos_register_id) {
                return PosCashSession::where('seller_id', $sellerId)
                    ->where('pos_register_id', $staff->pos_register_id)
                    ->open()
                    ->exists();
            }
        }
        return PosCashSession::where('seller_id', $sellerId)->open()->exists();
    }

    public function cash(Request $request)
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Caja';

        // Check if there are registers, if not create default
        $registers = \App\Models\PosRegister::where('seller_id', $seller->id)->get();
        if ($registers->isEmpty()) {
            $defaultRegister = \App\Models\PosRegister::create([
                'seller_id' => $seller->id,
                'name' => 'Caja General',
                'description' => 'Caja predeterminada del sistema',
                'is_active' => true
            ]);
            $registers = collect([$defaultRegister]);
        }

        // Determine which register we are viewing
        $selectedRegisterId = $request->get('register');
        if ($selectedRegisterId) {
            $selectedRegister = \App\Models\PosRegister::where('seller_id', $seller->id)->findOrFail($selectedRegisterId);
        } else {
            // Check if logged in staff has a register assigned
            $staffId = session()->get('seller_staff_id') ?? session()->get('pos_staff_id');
            $staff = $staffId ? \App\Models\PosStaff::find($staffId) : null;
            if ($staff && $staff->pos_register_id) {
                $selectedRegister = \App\Models\PosRegister::where('seller_id', $seller->id)->find($staff->pos_register_id);
            }
            if (!isset($selectedRegister) || !$selectedRegister) {
                // Try to find open session of any register
                $openSessionAny = PosCashSession::where('seller_id', $seller->id)->open()->first();
                if ($openSessionAny && $openSessionAny->pos_register_id) {
                    $selectedRegister = \App\Models\PosRegister::where('seller_id', $seller->id)->find($openSessionAny->pos_register_id);
                }
            }
            if (!isset($selectedRegister) || !$selectedRegister) {
                $selectedRegister = $registers->first();
            }
        }

        $openSession = PosCashSession::where('seller_id', $seller->id)
            ->where('pos_register_id', $selectedRegister->id)
            ->open()
            ->first();

        $sessions = PosCashSession::where('seller_id', $seller->id)
            ->where('pos_register_id', $selectedRegister->id)
            ->latest()
            ->limit(10)
            ->get();

        $transactions = $openSession
            ? PosTransaction::where('cash_session_id', $openSession->id)->latest()->limit(50)->get()
            : collect();

        return view('seller.cash', compact('pageTitle', 'seller', 'store', 'openSession', 'sessions', 'transactions', 'selectedRegister', 'registers'));
    }

    public function cashOpen(Request $request)
    {
        $seller = $this->seller();
        $registerId = $request->register_id;
        
        if (!$registerId) {
            // Try to find first active register
            $reg = \App\Models\PosRegister::where('seller_id', $seller->id)->active()->first();
            if (!$reg) {
                // Create one
                $reg = \App\Models\PosRegister::create([
                    'seller_id' => $seller->id,
                    'name' => 'Caja General',
                    'description' => 'Caja predeterminada',
                    'is_active' => true
                ]);
            }
            $registerId = $reg->id;
        }

        $register = \App\Models\PosRegister::where('seller_id', $seller->id)->findOrFail($registerId);

        if (!$register->is_active) {
            return back()->with('error', 'La caja "' . $register->name . '" está inactiva y no se puede abrir.');
        }

        if (PosCashSession::where('pos_register_id', $register->id)->open()->exists()) {
            return back()->with('error', 'La caja "' . $register->name . '" ya tiene una sesión abierta.');
        }

        $s = PosCashSession::create([
            'seller_id' => $seller->id,
            'pos_register_id' => $register->id,
            'opening_balance' => $request->opening_balance ?? 0,
            'opened_at' => now(), 'status' => 'open', 'notes' => $request->notes,
        ]);

        PosTransaction::create([
            'cash_session_id' => $s->id, 'seller_id' => $seller->id,
            'type' => 'cash_in', 'amount' => $request->opening_balance ?? 0,
            'description' => 'Apertura de ' . $register->name, 'payment_method' => 'cash',
        ]);
        return back()->with('success', $register->name . ' abierta con S/ ' . number_format($s->opening_balance, 2));
    }

    public function cashClose(Request $request)
    {
        $seller = $this->seller();
        $sessionId = $request->session_id;

        if ($sessionId) {
            $s = PosCashSession::where('seller_id', $seller->id)->open()->findOrFail($sessionId);
        } else {
            $s = PosCashSession::where('seller_id', $seller->id)->open()->firstOrFail();
        }

        $denominations = [];
        foreach ($request->all() as $key => $val) {
            if (str_starts_with($key, 'b_') && (int)$val > 0) {
                $denominations[str_replace('_', '.', substr($key, 2))] = (int) $val;
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $s, $request, $denominations) {
            $s->update([
                'closing_balance' => $request->closing_balance ?? 0,
                'closed_at' => now(), 'status' => 'closed',
                'notes' => json_encode([
                    'text' => $request->notes,
                    'denominations' => $denominations,
                ]),
            ]);
            $diff = ($request->closing_balance ?? 0) - $s->opening_balance - $s->total_sales - $s->total_cash_in + $s->total_expenses + $s->total_cash_out;
            if ($diff != 0) {
                PosTransaction::create([
                    'cash_session_id' => $s->id, 'seller_id' => $seller->id,
                    'type' => $diff > 0 ? 'cash_in' : 'cash_out',
                    'amount' => abs($diff), 'description' => 'Diferencia de cierre (arqueo)',
                    'payment_method' => 'cash',
                ]);
            }
        });

        return back()->with('success', 'Caja cerrada con arqueo. Saldo final: S/ ' . number_format($s->closing_balance, 2));
    }

    public function cashArqueo($id)
    {
        $seller = $this->seller();
        $store = $this->store();
        $s = PosCashSession::where('seller_id', $seller->id)->findOrFail($id);
        $notes = json_decode($s->notes, true) ?? [];
        $denominations = $notes['denominations'] ?? [];
        $notesText = $notes['text'] ?? '';
        $transactions = $s->transactions()->get();
        $diff = ($s->closing_balance ?? 0) - $s->opening_balance - $s->total_sales - $s->total_cash_in + $s->total_expenses + $s->total_cash_out;
        return view('seller.cash_arqueo_print', compact('seller', 'store', 's', 'notesText', 'denominations', 'transactions', 'diff'));
    }

    public function cashTransaction(Request $request)
    {
        $seller = $this->seller();
        $registerId = $request->register_id;
        
        if ($registerId) {
            $s = PosCashSession::where('seller_id', $seller->id)->where('pos_register_id', $registerId)->open()->first();
        } else {
            $s = $this->getActiveCashSession($seller->id);
        }

        if (!$s) {
            return back()->with('error', 'Debe abrir una caja antes de registrar una transacción.');
        }
        $type = $request->type; // cash_in or cash_out
        $amount = abs($request->amount ?? 0);
        PosTransaction::create([
            'cash_session_id' => $s->id, 'seller_id' => $seller->id,
            'type' => $type, 'amount' => $amount,
            'description' => $request->description, 'payment_method' => $request->payment_method ?? 'cash',
        ]);
        if ($type === 'cash_in') $s->increment('total_cash_in', $amount);
        else $s->increment('total_cash_out', $amount);
        return back()->with('success', 'Transacción registrada');
    }

    // ── POS Registers (Cajas) ──

    public function registers()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Cajas Registradoras';

        $registers = \App\Models\PosRegister::where('seller_id', $seller->id)
            ->withCount('cashSessions')
            ->with(['openSession', 'staff'])
            ->latest()
            ->get();

        // If no registers exist, create a default one
        if ($registers->isEmpty()) {
            \App\Models\PosRegister::create([
                'seller_id'   => $seller->id,
                'name'        => 'Caja General',
                'description' => 'Caja predeterminada del sistema',
                'is_active'   => true,
            ]);

            $registers = \App\Models\PosRegister::where('seller_id', $seller->id)
                ->withCount('cashSessions')
                ->with(['openSession', 'staff'])
                ->latest()
                ->get();
        }

        // Get plan register limit
        $activePackage = \App\Models\StorePackage::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->with('package')
            ->first();
        
        $maxRegisters = 5;
        if ($activePackage && $activePackage->package) {
            $features = (array) ($activePackage->package->features ?? []);
            foreach ($features as $f) {
                $fObj = (object) $f;
                if (isset($fObj->key) && $fObj->key === 'registers') {
                    $val = strtolower(trim($fObj->value));
                    if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                        $maxRegisters = 999999;
                    } elseif (is_numeric($val)) {
                        $maxRegisters = (int) $val;
                    }
                    break;
                }
            }
            if ($maxRegisters === 5 && isset($activePackage->package->max_registers)) {
                $maxRegisters = $activePackage->package->max_registers;
            }
        }

        $staff = \App\Models\PosStaff::where('seller_id', $seller->id)->where('status', 'active')->get();

        return view('seller.registers', compact('pageTitle', 'seller', 'store', 'registers', 'maxRegisters', 'staff'));
    }

    public function registerStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:60']);
        $seller = $this->seller();

        // Check plan limit
        $activePackage = \App\Models\StorePackage::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->with('package')
            ->first();
        
        $maxRegisters = 5;
        if ($activePackage && $activePackage->package) {
            $features = (array) ($activePackage->package->features ?? []);
            foreach ($features as $f) {
                $fObj = (object) $f;
                if (isset($fObj->key) && $fObj->key === 'registers') {
                    $val = strtolower(trim($fObj->value));
                    if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                        $maxRegisters = 999999;
                    } elseif (is_numeric($val)) {
                        $maxRegisters = (int) $val;
                    }
                    break;
                }
            }
            if ($maxRegisters === 5 && isset($activePackage->package->max_registers)) {
                $maxRegisters = $activePackage->package->max_registers;
            }
        }

        $current = \App\Models\PosRegister::where('seller_id', $seller->id)->count();
        if ($current >= $maxRegisters) {
            $limitLabel = $maxRegisters === 999999 ? 'Ilimitado' : $maxRegisters;
            return back()->with('error', "Tu plan permite un máximo de {$limitLabel} caja(s). Actualiza tu plan para agregar más.");
        }

        \App\Models\PosRegister::create([
            'seller_id'   => $seller->id,
            'name'        => trim($request->name),
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Caja "' . $request->name . '" creada exitosamente.');
    }

    public function registerToggle($id)
    {
        $register = \App\Models\PosRegister::where('seller_id', $this->seller()->id)->findOrFail($id);
        $register->update(['is_active' => !$register->is_active]);
        $status = $register->is_active ? 'activada' : 'desactivada';
        return back()->with('success', "Caja \"{$register->name}\" {$status}.");
    }

    public function registerDelete($id)
    {
        $seller   = $this->seller();
        $register = \App\Models\PosRegister::where('seller_id', $seller->id)->findOrFail($id);

        // Prevent deleting if has open session
        if ($register->cashSessions()->where('status', 'open')->exists()) {
            return back()->with('error', 'No se puede eliminar una caja con una sesión activa. Cierra la sesión primero.');
        }

        $name = $register->name;
        $register->delete();
        return back()->with('success', "Caja \"{$name}\" eliminada.");
    }

    public function registerAssignStaff(Request $request, $id)
    {
        $seller   = $this->seller();
        $register = \App\Models\PosRegister::where('seller_id', $seller->id)->findOrFail($id);

        // Unassign all staff from this register first
        \App\Models\PosStaff::where('seller_id', $seller->id)
            ->where('pos_register_id', $register->id)
            ->update(['pos_register_id' => null]);

        // Assign selected staff
        if ($request->has('staff_ids') && is_array($request->staff_ids)) {
            \App\Models\PosStaff::where('seller_id', $seller->id)
                ->whereIn('id', $request->staff_ids)
                ->update(['pos_register_id' => $register->id]);
        }

        return back()->with('success', "Personal asignado a \"{$register->name}\" correctamente.");
    }

    // ── Expenses ──


    public function expenses()
    {
        $seller = $this->seller(); $store = $this->store();
        $pageTitle = 'Gastos';
        $expenses = PosExpense::where('seller_id', $seller->id)->latest()->paginate(20);
        $categories = ['supplies' => 'Insumos', 'rent' => 'Alquiler', 'utilities' => 'Servicios', 'salary' => 'Sueldos', 'maintenance' => 'Mantenimiento', 'other' => 'Otros'];
        $catTotals = PosExpense::where('seller_id', $seller->id)->whereMonth('created_at', now()->month)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category');
        return view('seller.expenses', compact('pageTitle', 'seller', 'store', 'expenses', 'categories', 'catTotals'));
    }

    public function expenseStore(Request $request)
    {
        $request->validate(['category' => 'required', 'amount' => 'required|numeric|min:0', 'description' => 'required']);
        $seller = $this->seller();
        $s = PosCashSession::where('seller_id', $seller->id)->open()->first();
        if (!$s) {
            return back()->with('error', 'Debe abrir una caja antes de registrar un gasto.');
        }
        $expense = PosExpense::create([
            'seller_id' => $seller->id, 'cash_session_id' => $s?->id, 'category' => $request->category,
            'amount' => $request->amount, 'description' => $request->description,
            'provider' => $request->provider, 'invoice_number' => $request->invoice_number,
            'payment_method' => $request->payment_method ?? 'cash',
            'notes' => $request->notes, 'expense_date' => $request->expense_date ?? now(),
        ]);
        if ($s) { $s->increment('total_expenses', $expense->amount); }
        return back()->with('success', 'Gasto registrado');
    }

    public function expenseDelete($id)
    {
        PosExpense::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Gasto eliminado');
    }

    // ── Cancel Order ──

    public function cancelOrder(Request $request, $id)
    {
        $order = PosOrder::where('seller_id', $this->seller()->id)->with('items')->findOrFail($id);
        if ($order->status === 'cancelled') {
            return back()->with('error', 'El pedido ya fue anulado');
        }

        // A delivered order can still be annulled from Billing only while it
        // has not been charged or invoiced.  Paid/invoiced documents must use
        // the formal credit-note flow instead of silently disappearing.
        if (in_array($order->status, ['delivered', 'ready'])
            && in_array($order->payment_status, ['pending', 'credit'])
            && !$order->invoice_series) {
            $this->cancelUnpaidBillingOrder($order, $request->reason);
            return back()->with('success', 'Pedido #' . $order->order_no . ' anulado');
        }

        if ($order->status === 'delivered') {
            return back()->with('error', 'No se puede anular un pedido entregado que ya fue cobrado o facturado');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $request) {
            $stockMsg = '';
            if ($order->payment_status === 'paid') {
                $stockResult = $this->restoreStockForOrder($order->seller_id, $order->items, $order->id);
                if (count($stockResult['restored'])) {
                    $stockMsg = '. Stock devuelto: ' . implode(', ', $stockResult['restored']);
                }
            }

            $order->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
                'cancel_reason' => $request->reason, 'refund_amount' => $order->total,
            ]);

            if ($order->pos_table_id) {
                PosTable::where('id', $order->pos_table_id)
                    ->orWhere('linked_to_table_id', $order->pos_table_id)
                    ->update([
                        'status' => 'free',
                        'linked_to_table_id' => null
                    ]);
            }
        });

        return back()->with('success', 'Pedido #' . $order->order_no . ' cancelado');
    }

    private function cancelUnpaidBillingOrder(PosOrder $order, ?string $reason = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $reason) {
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason ?: 'Anulado antes del cobro',
                'refund_amount' => 0,
            ]);

            if ($order->pos_table_id) {
                PosTable::where('id', $order->pos_table_id)
                    ->orWhere('linked_to_table_id', $order->pos_table_id)
                    ->update(['status' => 'free', 'linked_to_table_id' => null]);
            }
        });
    }

    public function orderStatus(Request $request, $id)
    {
        $storeId = $this->store()?->id;
        if ($request->has('is_delivery')) {
            $order = DeliveryOrder::where('store_id', $storeId)->with('items', 'store', 'user')->findOrFail($id);

            $newStatus = $request->status;
            $allowedTransitions = [
                'pending'    => ['confirmed', 'cancelled'],
                'confirmed'  => ['preparing', 'cancelled'],
                'preparing'  => ['ready', 'cancelled'],
                'ready'      => ['on_the_way', 'cancelled'],
                'on_the_way' => ['delivered', 'cancelled'],
            ];

            if (!isset($allowedTransitions[$order->status]) || !in_array($newStatus, $allowedTransitions[$order->status])) {
                return back()->with('error', 'No puedes cambiar a este estado desde ' . $order->status);
            }

            $updateData = ['status' => $newStatus];
            if ($newStatus === 'cancelled') {
                $updateData['cancelled_at']  = now();
                $updateData['cancel_reason'] = $request->reason ?? 'Cancelado por la tienda';
            }

            $order->update($updateData);

            event(new DeliveryOrderStatusUpdated($order));

            if (in_array($newStatus, ['confirmed', 'ready'])) {
                broadcast(new NewJobAvailable($order, 'Nuevo pedido para reparto en ' . ($order->store->name ?? 'tienda')))->toOthers();
                FcmService::sendToAllCouriers(
                    'Nuevo pedido disponible',
                    'Hay un pedido listo para reparto en ' . ($order->store->name ?? 'tu zona'),
                    ['job_id' => (string) $order->id, 'order_no' => $order->order_no, 'job_type' => 'delivery']
                );
            }

            if ($order->user) {
                $cancelReason = $request->reason ?? 'Cancelado por la tienda';
                $statusLabels = [
                    'confirmed'  => ['Pedido confirmado', 'Tu pedido #' . $order->order_no . ' ha sido confirmado por la tienda'],
                    'preparing'  => ['Preparando tu pedido', 'La tienda está preparando tu pedido #' . $order->order_no],
                    'ready'      => ['Pedido listo', 'Tu pedido #' . $order->order_no . ' está listo para ser entregado'],
                    'on_the_way' => ['Pedido en camino', 'Tu pedido #' . $order->order_no . ' está en camino a tu dirección'],
                    'delivered'  => ['Pedido entregado', 'Tu pedido #' . $order->order_no . ' ha sido entregado. ¡Que lo disfrutes!'],
                    'cancelled'  => ['Pedido cancelado', 'Tu pedido #' . $order->order_no . ' ha sido cancelado: ' . $cancelReason],
                ];
                if (isset($statusLabels[$newStatus])) {
                    FcmService::sendToUser($order->user, $statusLabels[$newStatus][0], $statusLabels[$newStatus][1], [
                        'order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'order_status', 'status' => $newStatus,
                        'store_name' => $order->store->name ?? '',
                    ]);
                }
            }

            return back()->with('success', 'Estado actualizado a ' . $newStatus);
        } else {
            $order = PosOrder::where('seller_id', $this->seller()->id)->findOrFail($id);
            $order->update(['status' => $request->status]);
        }
        return back()->with('success', 'Estado actualizado');
    }

    // ── Invoice Detail & Downloads ──

    public function invoiceDetail($id)
    {
        $seller = $this->seller();
        $store = $this->store();
        $activeCompany = $this->activeCompany();
        $invoice = SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);
        $pageTitle = 'Comprobante ' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT);

        if (empty($invoice->hash) && !empty($invoice->xml_content)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $invoice->xml_content, $matches)) {
                $invoice->hash = trim($matches[1]);
                try { $invoice->save(); } catch (\Exception $e) {}
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $invoice->xml_content, $matches)) {
                $invoice->hash = trim($matches[1]);
                try { $invoice->save(); } catch (\Exception $e) {}
            }
        }

        $xmlFormatted = $invoice->xml_content ? $this->formatXml($invoice->xml_content) : null;
        $cdrData = $invoice->cdr_response;
        $sunatResponse = $invoice->sunat_response;
        $errors = $invoice->errors;

        return view('seller.invoice_detail', compact('pageTitle', 'seller', 'store', 'activeCompany', 'invoice', 'xmlFormatted', 'cdrData', 'sunatResponse', 'errors'));
    }

    public function invoicePdf($id, $format = 'a4')
    {
        $seller = $this->seller();
        $invoice = SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);
        $store = $this->store();

        $company = $invoice->company ?? $this->activeCompany();
        $docNumber = $company?->document_number ?? $seller->document_number ?? '';
        $businessName = $company?->business_name ?? $seller->business_name ?? $seller->name ?? '';
        $tradeName = $company?->trade_name ?? $seller->trade_name ?? '';
        $address = $company?->address ?? $seller->address ?? '';
        $ubigeo = $company?->ubigeo ?? $seller->ubigeo ?? '';

        $logoBase64 = null;
        if ($store && $store->image) {
            $logoPath = public_path('assets/images/store/' . $store->image);
            if (!file_exists($logoPath)) {
                $logoPath = base_path('../assets/images/store/' . $store->image);
            }
            if (file_exists($logoPath)) {
                try {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
        }

        $hashVal = $invoice->hash;
        $xmlDigest = null;
        if (!empty($invoice->xml_content)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $invoice->xml_content, $matches)) {
                $xmlDigest = trim($matches[1]);
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $invoice->xml_content, $matches)) {
                $xmlDigest = trim($matches[1]);
            }
        }

        if ($xmlDigest) {
            $hashVal = $xmlDigest;
            if ($invoice->hash !== $xmlDigest) {
                $invoice->hash = $xmlDigest;
                try {
                    $invoice->save();
                } catch (\Exception $e) {}
            }
        } else {
            if ($hashVal && (str_contains($hashVal, '-') || strlen($hashVal) < 15)) {
                $hashVal = '';
            }
        }
        $hashVal = $hashVal ?? '';

        // Calculate totals for SUNAT QR
        $totTotal = (double)($invoice->total ?? 0);
        $totIgv = (double)($invoice->total_igv ?? 0);
        if ($totIgv == 0 && ($invoice->total_gravada ?? 0) > 0) {
            $totIgv = round($totTotal - ($invoice->total_gravada ?? 0), 2);
        }

        $qrData = "{$docNumber}|{$invoice->tipo_doc}|{$invoice->serie}|" . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . "|{$totIgv}|{$totTotal}|" . ($invoice->fecha_emision ? $invoice->fecha_emision->format('Y-m-d') : '') . "|{$invoice->cliente_tipo_doc}|{$invoice->cliente_num_doc}|{$hashVal}|";

        $qrBase64 = null;
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                // Try generating PNG explicitly (QRGdImagePNG returns a complete data URI: data:image/png;base64,...)
                $options = new \chillerlan\QRCode\QROptions([
                    'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class
                ]);
                $qr = new \chillerlan\QRCode\QRCode($options);
                $qrBase64 = $qr->render($qrData);
            } catch (\Exception $e) {
                \Log::warning('QR PNG generation failed, trying SVG fallback: ' . $e->getMessage());
                try {
                    // Fallback to default (usually SVG data URI) or explicit SVG
                    $options = new \chillerlan\QRCode\QROptions([
                        'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class
                    ]);
                    $qr = new \chillerlan\QRCode\QRCode($options);
                    $qrBase64 = $qr->render($qrData);
                } catch (\Exception $ex) {
                    \Log::error('QR SVG fallback generation also failed: ' . $ex->getMessage());
                    $qrBase64 = null;
                }
            }
        }

        $montoLetras = $this->numeroALetrasPdf($invoice->total);

        // Resolve unit codes and SUNAT product codes for each item
        $itemDetails = [];
        if ($invoice->isConsumptionSummary()) {
            $itemDetails[] = [
                'quantity' => 1,
                'unit_code' => 'NIU',
                'sunat_code' => null,
                'name' => $invoice->consumption_description ?: 'Consumo',
                'tax_type' => $invoice->total_exonerada > 0 ? 'exonerado' : ($invoice->total_inafecta > 0 ? 'inafecto' : 'gravado'),
                'unit_price' => $invoice->total,
            ];
        } elseif ($invoice->order && $invoice->order->items->count()) {
            foreach ($invoice->order->items as $item) {
                $product = $item->product;
                $unitCode = 'NIU';
                $sunatCode = null;

                if ($product) {
                    if ($product->stock_type === 'packaged') {
                        $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
                        if ($link && $link->item) {
                            $unitCode = strtoupper($link->item->unit ?? 'NIU');
                            $sunatCode = $link->item->sunat_code;
                        }
                    } elseif ($product->stock_type === 'prepared') {
                        $recipe = \App\Models\InvRecipe::where('product_id', $product->id)->active()->first();
                        if ($recipe && $recipe->unit_produced) {
                            $unitCode = strtoupper($recipe->unit_produced);
                        }
                    }
                }

                $itemDetails[] = [
                    'quantity'  => $item->quantity,
                    'unit_code' => $unitCode,
                    'sunat_code'=> $sunatCode,
                    'name'      => $item->product_name,
                    'tax_type'  => $product ? ($product->tax_type ?? 'gravado') : 'gravado',
                    'unit_price'=> $item->unit_price,
                ];
            }
        }

        $viewName = $format === 'ticket' ? 'seller.invoice_pdf_ticket' : ($format === 'a5' ? 'seller.invoice_pdf_a5' : 'seller.invoice_pdf_a4');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, compact(
            'invoice', 'seller', 'company', 'docNumber', 'businessName', 'tradeName',
            'address', 'qrBase64', 'qrData', 'montoLetras', 'store', 'logoBase64', 'itemDetails'
        ));

        $paperSize = $format === 'a5' ? 'a5' : ($format === 'ticket' ? [0, 0, 226.77, 600] : 'a4');

        if (is_array($paperSize)) {
            $pdf->setPaper($paperSize);
        } else {
            $pdf->setPaper($paperSize, 'portrait');
        }

        // Standard SUNAT naming: [RUC]-[TIPO_COMPROBANTE]-[SERIE]-[CORRELATIVO]
        $ruc = $docNumber ?: '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03'; // Default to boleta '03'
        $filename = $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->download($filename);
    }

    public function invoiceCdr($id)
    {
        $invoice = SunatInvoice::where('seller_id', $this->seller()->id)->findOrFail($id);
        $cdr = $invoice->getRawOriginal('cdr_response');
        if (!$cdr) {
            return back()->with('error', 'No hay CDR disponible para este comprobante.');
        }

        $company = $invoice->company ?? $this->activeCompany();
        $ruc = $company?->document_number ?? $this->seller()->document_number ?? '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03';
        $baseName = 'R-' . $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT);

        // If cdr_response is stored as actual XML, serve it directly
        if (str_starts_with(trim($cdr), '<')) {
            $filename = $baseName . '.xml';
            return response()->make($cdr, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }
        // Otherwise decode JSON and look for archivedCdr or cdrContent
        $data = json_decode($cdr, true);
        $cdrContent = $data['archivedCdr'] ?? $data['cdrContent'] ?? null;
        if ($cdrContent) {
            $filename = $baseName . '.zip';
            return response()->make(base64_decode($cdrContent), 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }
        return back()->with('error', 'El CDR está disponible solo como respuesta JSON. No se puede descargar como archivo independiente.');
    }

    public function invoiceXml($id)
    {
        $invoice = SunatInvoice::where('seller_id', $this->seller()->id)->findOrFail($id);
        if (!$invoice->xml_content) {
            return back()->with('error', 'No hay XML disponible para este comprobante.');
        }

        $company = $invoice->company ?? $this->activeCompany();
        $ruc = $company?->document_number ?? $this->seller()->document_number ?? '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03';
        $filename = $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . '.xml';

        return response()->make($invoice->xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function invoiceVoid(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $seller = $this->seller();
        $isCashOpen = PosCashSession::where('seller_id', $seller->id)->open()->exists();
        if (!$isCashOpen) {
            return back()->with('error', 'Debe abrir una caja antes de anular un comprobante.');
        }

        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'No hay una empresa activa seleccionada.');
        }

        $store = $this->store();
        if ($store && $store->hasReachedInvoiceLimit()) {
            $planName = $store->activePackagesRelation()->first()?->package?->name ?? 'Actual';
            $limit = $store->getInvoiceLimit() ?? 50;
            return back()->with('error', "Has alcanzado el límite de {$limit} comprobantes electrónicos de tu plan {$planName}. Actualiza a un plan superior para facturación ilimitada.");
        }

        $invoice = SunatInvoice::where('seller_id', $seller->id)
            ->whereIn('cdr_status', [SunatInvoice::STATUS_ACCEPTED, SunatInvoice::STATUS_PENDING])
            ->findOrFail($id);

        if ($invoice->tipo_doc === 'NV') {
            try {
                $order = $invoice->order;
                $stockMsg = '';
                if ($order) {
                    $stockResult = $this->restoreStockForOrder($seller->id, $order->items, $invoice->id);
                    if (count($stockResult['restored'])) {
                        $stockMsg .= 'Stock devuelto: ' . implode(', ', $stockResult['restored']) . '. ';
                    }
                    if (count($stockResult['skipped'])) {
                        $stockMsg .= 'Productos preparados (no se devuelve stock): ' . implode(', ', $stockResult['skipped']) . '. ';
                    }

                    // Reverse cash session: register refund transaction
                    $cashSession = \App\Models\PosCashSession::where('seller_id', $seller->id)->open()->first();
                    if ($cashSession) {
                        \App\Models\PosTransaction::create([
                            'cash_session_id' => $cashSession->id,
                            'seller_id'       => $seller->id,
                            'pos_order_id'    => $order->id,
                            'type'            => 'cash_out',
                            'amount'          => $order->total,
                            'description'     => 'Anulación interna comprobante ' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT),
                            'payment_method'  => 'cash',
                        ]);
                        $cashSession->increment('total_cash_out', $order->total);
                    }

                    // Mark order as cancelled
                    $order->update([
                        'status'          => 'cancelled',
                        'cancelled_at'    => now(),
                        'cancel_reason'   => 'Anulación interna: ' . $request->reason,
                    ]);
                    if ($order->pos_table_id) {
                        \App\Models\PosTable::where('id', $order->pos_table_id)
                            ->orWhere('linked_to_table_id', $order->pos_table_id)
                            ->update([
                                'status' => 'free',
                                'linked_to_table_id' => null
                            ]);
                    }
                }

                $invoice->update(['cdr_status' => SunatInvoice::STATUS_CANCELLED]);
                return back()->with('success', 'Nota de Venta anulada internamente. ' . $stockMsg);
            } catch (\Exception $e) {
                return back()->with('error', 'Error al anular internamente: ' . $e->getMessage());
            }
        }

        $useVoided = in_array($invoice->tipo_doc, ['01', '07', '08']);
        $ncSeries = null;
        if (!$useVoided) {
            $ncSeries = PosInvoiceSeries::where('seller_company_id', $activeCompany->id)
                ->whereHas('invoiceType', fn($q) => $q->where('code', '07'))
                ->where('active', true)
                ->first();

            if (!$ncSeries) {
                return back()->with('error', 'No tienes una serie de Nota de Crédito configurada. Agrega una serie para el tipo 07 - Nota de Crédito en la sección Series.');
            }
        }

        try {
            $sunatService = new \App\Services\SunatService($activeCompany);
            $order = $invoice->order;

            if ($order) {
                if ($useVoided) {
                    $docFecha = null;
                    if (!empty($invoice->xml_content) && preg_match('/<cbc:IssueDate>([^<]+)<\/cbc:IssueDate>/i', $invoice->xml_content, $m)) {
                        $docFecha = $m[1];
                    }
                    if (!$docFecha && $invoice->fecha_emision) {
                        $docFecha = \Carbon\Carbon::parse($invoice->fecha_emision)->format('Y-m-d');
                    }
                    if (!$docFecha && $invoice->created_at) {
                        $docFecha = \Carbon\Carbon::parse($invoice->created_at)->utc()->format('Y-m-d');
                    }
                    if (!$docFecha) {
                        $docFecha = now()->format('Y-m-d');
                    }
                    $result = $sunatService->sendVoided($order, $invoice->tipo_doc, $invoice->serie, (int) $invoice->correlativo, $request->reason, null, $docFecha);
                } else {
                    $result = $sunatService->sendNote($order, $ncSeries, '01', $request->reason, null, $invoice->tipo_doc);
                }

                $stockResult = $this->restoreStockForOrder($seller->id, $order->items, $invoice->id);
                $stockMsg = '';
                if (count($stockResult['restored'])) {
                    $stockMsg .= 'Stock devuelto: ' . implode(', ', $stockResult['restored']) . '. ';
                }
                if (count($stockResult['skipped'])) {
                    $stockMsg .= 'Productos preparados (no se devuelve stock): ' . implode(', ', $stockResult['skipped']) . '. ';
                }

                // Reverse cash session: register refund transaction
                $cashSession = \App\Models\PosCashSession::where('seller_id', $seller->id)->open()->first();
                if ($cashSession) {
                    \App\Models\PosTransaction::create([
                        'cash_session_id' => $cashSession->id,
                        'seller_id'       => $seller->id,
                        'pos_order_id'    => $order->id,
                        'type'            => 'cash_out',
                        'amount'          => $order->total,
                        'description'     => 'Anulación comprobante ' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT),
                        'payment_method'  => 'cash',
                    ]);
                    $cashSession->increment('total_cash_out', $order->total);
                }

                // Mark order as cancelled
                $order->update([
                    'status'          => 'cancelled',
                    'cancelled_at'    => now(),
                    'cancel_reason'   => 'Anulación SUNAT: ' . $request->reason,
                ]);
                if ($order->pos_table_id) {
                    \App\Models\PosTable::where('id', $order->pos_table_id)
                        ->orWhere('linked_to_table_id', $order->pos_table_id)
                        ->update([
                            'status' => 'free',
                            'linked_to_table_id' => null
                        ]);
                }

                $invoice->update(['cdr_status' => SunatInvoice::STATUS_CANCELLED]);
                if ($useVoided) {
                    return back()->with('success', 'Comunicación de Baja enviada: ' . $result->serie . '-' . str_pad($result->correlativo, 6, '0', STR_PAD_LEFT) . '. ' . $stockMsg);
                }
                return back()->with('success', 'Nota de Crédito emitida: ' . $result->serie . '-' . str_pad($result->correlativo, 8, '0', STR_PAD_LEFT) . '. ' . $stockMsg);
            }

            $invoice->update(['cdr_status' => SunatInvoice::STATUS_CANCELLED]);
            return back()->with('success', 'Comprobante marcado como anulado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al emitir nota de crédito: ' . $e->getMessage());
        }
    }

    public function resendToSunat($id)
    {
        $seller = $this->seller();
        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return back()->with('error', 'No hay una empresa activa seleccionada.');
        }

        $invoice = SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);

        if (!in_array($invoice->cdr_status, [SunatInvoice::STATUS_PENDING, SunatInvoice::STATUS_ERROR, SunatInvoice::STATUS_REJECTED])) {
            return back()->with('error', 'Solo se pueden reenviar comprobantes en estado Pendiente, Error o Rechazado.');
        }

        $order = $invoice->order;
        if (!$order) {
            return back()->with('error', 'No se encontró la orden asociada a este comprobante.');
        }

        $series = PosInvoiceSeries::where('seller_company_id', $activeCompany->id)
            ->where('series', $invoice->serie)
            ->where('active', true)
            ->with('invoiceType')
            ->first();

        if (!$series) {
            return back()->with('error', 'La serie ' . $invoice->serie . ' no está configurada o está inactiva.');
        }

        try {
            $sunatService = new \App\Services\SunatService($activeCompany);

            $clientData = [
                'tipo_doc' => $invoice->cliente_tipo_doc ?? '6',
                'num_doc'  => $invoice->cliente_num_doc ?? '-',
                'nombre'   => $invoice->cliente_nombre ?? 'CLIENTE VARIOS',
            ];

            if (in_array($invoice->tipo_doc, ['07', '08'])) {
                $motivo = $invoice->note_motivo ?? '01';
                $descripcion = $invoice->note_description
                    ?? trim(str_replace('Anulación SUNAT: ', '', (string) ($order->cancel_reason ?? '')))
                    ?: 'Anulación de comprobante';
                $result = $sunatService->sendNote($order, $series, $motivo, $descripcion, $invoice);
            } else {
                $result = $sunatService->sendInvoice($order, $series, $clientData, $invoice->correlativo, false, $invoice);
            }

            if ($result->cdr_status === SunatInvoice::STATUS_ACCEPTED) {
                $number = str_pad($result->correlativo, 8, '0', STR_PAD_LEFT);
                $obsMsg = '';
                $cdrData = $result->cdr_response;
                if (is_array($cdrData) && !empty($cdrData['notes'])) {
                    $obsMsg = ' con observaciones: ' . implode(', ', $cdrData['notes']);
                }
                return back()->with('success', 'Comprobante ' . $result->serie . '-' . $number . ' reenviado y aceptado por SUNAT.' . $obsMsg);
            } else {
                $msg = 'Rechazo o error desconocido';
                $cdrData = $result->cdr_response;
                if (is_array($cdrData)) {
                    $msg = $cdrData['description'] ?? $cdrData['desc'] ?? 'Rechazado';
                    if (!empty($cdrData['notes'])) {
                        $msg .= ' (Obs: ' . implode(', ', $cdrData['notes']) . ')';
                    }
                } elseif ($result->errors) {
                    $errors = $result->errors;
                    if (is_array($errors) && !empty($errors)) {
                        $msgList = [];
                        foreach ($errors as $e) {
                            $msgList[] = is_array($e) ? (($e['code'] ?? '') . ': ' . ($e['message'] ?? '')) : $e;
                        }
                        $msg = implode(', ', $msgList);
                    }
                }
                return back()->with('error', 'SUNAT rechazó el reenvío o conexión: ' . $msg);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al reenviar a SUNAT: ' . $e->getMessage());
        }
    }

    public function resendReceipt(Request $request, $orderId)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $seller = $this->seller();
        $order = PosOrder::where('seller_id', $seller->id)->with('items', 'table')->findOrFail($orderId);

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Solo se pueden reenviar comprobantes de pedidos pagados.');
        }

        $store = $this->store();
        $company = $this->activeCompany();

        try {
            $mailService = new \App\Notify\Email();
            $subject = ($store?->name ?? 'Restaurante') . ' - Comprobante #' . $order->order_no;

            $htmlContent = view('seller.pos.ticket', compact('order', 'store', 'company'))->render();

            $mailService->send($request->email, $subject, $htmlContent);

            return back()->with('success', 'Comprobante enviado a ' . $request->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al enviar correo: ' . $e->getMessage());
        }
    }

    private function numeroALetrasPdf($num): string
    {
        $num = round((float) $num, 2);
        $entero = (int) floor($num);
        $centimos = (int) round(($num - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];

        $convertir = function ($n) use (&$convertir, $unidades, $decenas, $especiales) {
            if ($n == 0) return '';
            if ($n < 10) return $unidades[$n];
            if ($n < 20) return $especiales[$n - 10];
            if ($n < 100) {
                $d = (int) floor($n / 10);
                $u = $n % 10;
                return $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
            }
            if ($n < 1000) {
                $c = (int) floor($n / 100);
                $resto = $n % 100;
                $prefix = $c === 1 ? 'CIENTO' : ['','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'][$c];
                return $prefix . ($resto > 0 ? ' ' . $convertir($resto) : '');
            }
            if ($n < 1000000) {
                $miles = (int) floor($n / 1000);
                $resto = $n % 1000;
                $txtMiles = $miles === 1 ? 'MIL' : $convertir($miles) . ' MIL';
                return $txtMiles . ($resto > 0 ? ' ' . $convertir($resto) : '');
            }
            return number_format($n);
        };

        $letras = $entero == 0 ? 'CERO' : $convertir($entero);
        return $letras . ' CON ' . str_pad($centimos, 2, '0', STR_PAD_LEFT) . '/100';
    }

    private function formatXml($xml)
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        @$dom->loadXML($xml);
        return $dom->saveXML();
    }

    private function consumeStockForOrder($sellerId, $orderItems): array
    {
        return (new StockService($sellerId))->consumeForOrder($orderItems);
    }

    private function restoreStockForOrder($sellerId, $orderItems, $referenceId): array
    {
        return (new StockService($sellerId))->restoreForOrder($orderItems, $referenceId);
    }

    // ── Staff Management ──
    public function staff()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Meseros / Personal - Terminal POS';
        $staff = \App\Models\PosStaff::where('seller_id', $seller->id)->with('company')->latest()->get();
        $companies = \App\Models\SellerCompany::where('seller_id', $seller->id)->get();

        return view('seller.pos.staff', compact('pageTitle', 'seller', 'store', 'staff', 'companies'));
    }

    public function staffStore(Request $request)
    {
        $seller = $this->seller();

        // Check active package limit for users
        $activePackage = \App\Models\StorePackage::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->with('package')
            ->first();

        $maxUsers = 999999;
        if ($activePackage && $activePackage->package) {
            $features = (array) ($activePackage->package->features ?? []);
            foreach ($features as $f) {
                $fObj = (object) $f;
                if (isset($fObj->key) && $fObj->key === 'users') {
                    $val = strtolower(trim($fObj->value));
                    if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                        $maxUsers = 999999;
                    } elseif (is_numeric($val)) {
                        $maxUsers = (int) $val;
                    }
                    break;
                }
            }
        }

        $currentActiveUsers = \App\Models\PosStaff::where('seller_id', $seller->id)->where('status', 'active')->count();
        if ($currentActiveUsers >= $maxUsers) {
            $limitLabel = $maxUsers === 999999 ? 'Ilimitado' : $maxUsers;
            return back()->with('error', "Tu plan permite un máximo de {$limitLabel} usuarios/personal activo. Actualiza tu plan para agregar más.");
        }

        $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'nullable|email|max:100|unique:pos_staff,email',
            'phone'             => 'nullable|string|max:20',
            'commission_rate'   => 'required|numeric|min:0|max:100',
            'document_number'   => 'nullable|string|max:20',
            'position'          => 'required|string|max:50',
            'hire_date'         => 'nullable|date',
            'salary_type'       => 'required|in:monthly,daily,hourly',
            'base_salary'       => 'required|numeric|min:0',
            'seller_company_id' => 'required|exists:seller_companies,id',
            'password'          => 'nullable|string|min:4',
            'permissions'       => 'nullable|array',
        ]);

        \App\Models\PosStaff::create([
            'seller_id'         => $seller->id,
            'seller_company_id' => $request->seller_company_id,
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password ? Hash::make($request->password) : null,
            'phone'             => $request->phone,
            'commission_rate'   => (float) $request->commission_rate,
            'document_number'   => $request->document_number,
            'position'          => $request->position,
            'hire_date'         => $request->hire_date,
            'salary_type'       => $request->salary_type,
            'base_salary'       => (float) $request->base_salary,
            'status'            => $request->status ?? 'active',
            'permissions'       => $this->staffPermissions($request),
        ]);

        return back()->with('success', 'Personal registrado correctamente.');
    }

    public function staffUpdate(Request $request, $id)
    {
        $seller = $this->seller();
        $member = \App\Models\PosStaff::where('seller_id', $seller->id)->findOrFail($id);

        if ($request->status === 'active' && $member->status !== 'active') {
            // Check active package limit for users
            $activePackage = \App\Models\StorePackage::where('seller_id', $seller->id)
                ->where('status', 'active')
                ->with('package')
                ->first();

            $maxUsers = 999999;
            if ($activePackage && $activePackage->package) {
                $features = (array) ($activePackage->package->features ?? []);
                foreach ($features as $f) {
                    $fObj = (object) $f;
                    if (isset($fObj->key) && $fObj->key === 'users') {
                        $val = strtolower(trim($fObj->value));
                        if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                            $maxUsers = 999999;
                        } elseif (is_numeric($val)) {
                            $maxUsers = (int) $val;
                        }
                        break;
                    }
                }
            }

            $currentActiveUsers = \App\Models\PosStaff::where('seller_id', $seller->id)->where('status', 'active')->count();
            if ($currentActiveUsers >= $maxUsers) {
                $limitLabel = $maxUsers === 999999 ? 'Ilimitado' : $maxUsers;
                return back()->with('error', "Tu plan permite un máximo de {$limitLabel} usuarios/personal activo. Actualiza tu plan para agregar más.");
            }
        }

        $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'nullable|email|max:100|unique:pos_staff,email,' . $id,
            'phone'             => 'nullable|string|max:20',
            'commission_rate'   => 'required|numeric|min:0|max:100',
            'document_number'   => 'nullable|string|max:20',
            'position'          => 'required|string|max:50',
            'hire_date'         => 'nullable|date',
            'salary_type'       => 'required|in:monthly,daily,hourly',
            'base_salary'       => 'required|numeric|min:0',
            'seller_company_id' => 'required|exists:seller_companies,id',
            'password'          => 'nullable|string|min:4',
            'permissions'       => 'nullable|array',
            'status'            => 'required|in:active,inactive',
        ]);

        $data = [
            'name'              => $request->name,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'commission_rate'   => (float) $request->commission_rate,
            'document_number'   => $request->document_number,
            'position'          => $request->position,
            'hire_date'         => $request->hire_date,
            'salary_type'       => $request->salary_type,
            'base_salary'       => (float) $request->base_salary,
            'status'            => $request->status,
            'seller_company_id' => $request->seller_company_id,
            'permissions'       => $this->staffPermissions($request),
        ];

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $member->update($data);

        return back()->with('success', 'Datos de personal actualizados.');
    }

    private function staffPermissions(Request $request): array
    {
        $permissions = (array) ($request->permissions ?? []);
        if ($request->position === 'contabilidad') {
            $permissions = array_merge($permissions, ['reports', 'inventory', 'accounting', 'hr']);
        }
        return array_values(array_unique($permissions));
    }

    public function staffDelete($id)
    {
        $seller = $this->seller();
        $member = \App\Models\PosStaff::where('seller_id', $seller->id)->findOrFail($id);
        $member->delete();

        return back()->with('success', 'Personal eliminado correctamente.');
    }

    // ── Waiter reports / Commissions ──
    public function staffReports()
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'El reporte de comisiones sólo está disponible en el plan Premium.');
        }
        $pageTitle = 'Reporte de Comisiones por Mesero';

        $dateFrom = request('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = request('to', now()->format('Y-m-d'));

        // Query all staff with their sales inside range
        $staff = \App\Models\PosStaff::where('seller_id', $seller->id)->get();
        $reports = [];

        foreach ($staff as $member) {
            // Get paid pos orders for this waiter in range
            $orders = PosOrder::where('seller_id', $seller->id)
                ->where('pos_staff_id', $member->id)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->get();

            $totalSales = $orders->sum('total');
            $commission = ($totalSales * $member->commission_rate) / 100;

            $reports[] = [
                'member'      => $member,
                'orders_count'=> $orders->count(),
                'total_sales' => $totalSales,
                'commission'  => $commission,
            ];
        }

        // General stats
        $totalSalesAll = array_sum(array_column($reports, 'total_sales'));
        $totalCommissionsAll = array_sum(array_column($reports, 'commission'));
        $totalOrdersAll = array_sum(array_column($reports, 'orders_count'));

        // Detailed orders table
        $detailedOrders = PosOrder::where('seller_id', $seller->id)
            ->whereNotNull('pos_staff_id')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('staff', 'table')
            ->latest()
            ->paginate(25);

        return view('seller.pos.staff_reports', compact(
            'pageTitle', 'seller', 'store', 'reports', 'dateFrom', 'dateTo',
            'totalSalesAll', 'totalCommissionsAll', 'totalOrdersAll', 'detailedOrders'
        ));
    }

    // ── HR Attendance ──
    public function attendance()
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'El control de asistencia sólo está disponible en el plan Premium.');
        }
        $pageTitle = 'Control de Asistencia - RR.HH';

        $date = request('date', now()->format('Y-m-d'));

        $staff = PosStaff::where('seller_id', $seller->id)->where('status', 'active')->orderBy('name')->get();
        $attendances = PosStaffAttendance::whereIn('pos_staff_id', $staff->pluck('id'))->where('date', $date)->get()->keyBy('pos_staff_id');

        return view('seller.pos.hr.attendance', compact('pageTitle', 'seller', 'store', 'staff', 'attendances', 'date'));
    }

    public function attendanceStore(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'El control de asistencia sólo está disponible en el plan Premium.');
        }
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
        ]);

        foreach ($request->attendance as $staffId => $data) {
            $member = PosStaff::where('seller_id', $seller->id)->findOrFail($staffId);

            $clockIn = !empty($data['clock_in']) ? $request->date . ' ' . $data['clock_in'] : null;
            $clockOut = !empty($data['clock_out']) ? $request->date . ' ' . $data['clock_out'] : null;

            $hours = 0.00;
            if ($clockIn && $clockOut) {
                $start = new \DateTime($clockIn);
                $end = new \DateTime($clockOut);
                $diff = $start->diff($end);
                $hours = $diff->h + ($diff->i / 60);
            }

            PosStaffAttendance::updateOrCreate(
                ['pos_staff_id' => $member->id, 'date' => $request->date],
                [
                    'status'    => $data['status'] ?? 'present',
                    'clock_in'  => $clockIn,
                    'clock_out' => $clockOut,
                    'hours_worked' => $hours,
                    'notes'     => $data['notes'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Asistencia del día guardada correctamente.');
    }

    public function attendanceClock(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'El control de asistencia sólo está disponible en el plan Premium.');
        }
        $request->validate([
            'document_number' => 'required|string',
            'action'          => 'required|in:clock_in,clock_out',
        ]);

        $seller = $this->seller();
        $member = PosStaff::where('seller_id', $seller->id)->where('document_number', $request->document_number)->first();

        if (!$member) {
            return back()->with('error', 'Número de documento no registrado.');
        }

        $date = now()->format('Y-m-d');
        $now = now()->format('Y-m-d H:i:s');

        $attendance = PosStaffAttendance::firstOrNew(['pos_staff_id' => $member->id, 'date' => $date]);

        if ($request->action === 'clock_in') {
            if ($attendance->clock_in) {
                return back()->with('error', "{$member->name} ya registró ENTRADA hoy a las " . \Carbon\Carbon::parse($attendance->clock_in)->format('H:i'));
            }
            $attendance->clock_in = $now;
            $attendance->status = 'present';
            $attendance->save();
            return back()->with('success', "ENTRADA registrada para {$member->name} a las " . now()->format('H:i'));
        } else {
            if (!$attendance->clock_in) {
                return back()->with('error', "{$member->name} no ha registrado ENTRADA hoy. Debe marcar entrada primero.");
            }
            if ($attendance->clock_out) {
                return back()->with('error', "{$member->name} ya registró SALIDA hoy a las " . \Carbon\Carbon::parse($attendance->clock_out)->format('H:i'));
            }
            $attendance->clock_out = $now;
            
            $start = new \DateTime($attendance->clock_in);
            $end = new \DateTime($now);
            $diff = $start->diff($end);
            $attendance->hours_worked = $diff->h + ($diff->i / 60);
            $attendance->save();
            return back()->with('success', "SALIDA registrada para {$member->name} a las " . now()->format('H:i') . " (" . number_format($attendance->hours_worked, 1) . " horas trabajadas)");
        }
    }

    // ── HR Payroll ──
    public function payroll()
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'La gestión de planillas sólo está disponible en el plan Premium.');
        }
        $pageTitle = 'Planilla y Pago de Nómina - RR.HH';

        $payrolls = PosStaffPayroll::where('seller_id', $seller->id)
            ->with('staff')
            ->latest()
            ->paginate(15);

        return view('seller.pos.hr.payroll', compact('pageTitle', 'seller', 'store', 'payrolls'));
    }

    public function payrollCalculate(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'La gestión de planillas sólo está disponible en el plan Premium.');
        }
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $staff = PosStaff::where('seller_id', $seller->id)->where('status', 'active')->get();
        $calculatedCount = 0;

        foreach ($staff as $member) {
            $daysInPeriod = (new \DateTime($request->period_start))->diff(new \DateTime($request->period_end))->days + 1;
            
            $baseSalaryEarned = 0;
            if ($member->salary_type === 'monthly') {
                $baseSalaryEarned = ($member->base_salary / 30) * $daysInPeriod;
            } elseif ($member->salary_type === 'daily') {
                $workedDays = PosStaffAttendance::where('pos_staff_id', $member->id)
                    ->whereBetween('date', [$request->period_start, $request->period_end])
                    ->whereIn('status', ['present', 'late'])
                    ->count();
                $baseSalaryEarned = $member->base_salary * $workedDays;
            } else { // hourly
                $workedHours = PosStaffAttendance::where('pos_staff_id', $member->id)
                    ->whereBetween('date', [$request->period_start, $request->period_end])
                    ->sum('hours_worked');
                $baseSalaryEarned = $member->base_salary * $workedHours;
            }

            $commissionsSales = PosOrder::where('seller_id', $seller->id)
                ->where('pos_staff_id', $member->id)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$request->period_start . ' 00:00:00', $request->period_end . ' 23:59:59'])
                ->sum('total');

            $commissionsEarned = ($commissionsSales * $member->commission_rate) / 100;

            PosStaffPayroll::updateOrCreate(
                [
                    'seller_id'    => $seller->id,
                    'pos_staff_id' => $member->id,
                    'period_start' => $request->period_start,
                    'period_end'   => $request->period_end
                ],
                [
                    'base_salary_earned' => $baseSalaryEarned,
                    'commissions_earned' => $commissionsEarned,
                    'net_salary'         => $baseSalaryEarned + $commissionsEarned,
                    'payment_status'     => 'pending',
                ]
            );
            $calculatedCount++;
        }

        return back()->with('success', "Planilla calculada exitosamente para {$calculatedCount} trabajadores.");
    }

    public function payrollPay(Request $request, $id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'La gestión de planillas sólo está disponible en el plan Premium.');
        }
        $payroll = PosStaffPayroll::where('seller_id', $seller->id)->findOrFail($id);

        $request->validate([
            'bonuses'        => 'nullable|numeric|min:0',
            'deductions'     => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'notes'          => 'nullable|string|max:500',
        ]);

        $bonuses = (float) ($request->bonuses ?? 0);
        $deductions = (float) ($request->deductions ?? 0);
        $net = $payroll->base_salary_earned + $payroll->commissions_earned + $bonuses - $deductions;

        $payroll->update([
            'bonuses'        => $bonuses,
            'deductions'     => $deductions,
            'net_salary'     => $net,
            'payment_status' => 'paid',
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => $request->payment_method,
            'notes'          => $request->notes,
        ]);

        // Registrar egreso en caja automáticamente
        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        if ($cashSession) {
            PosExpense::create([
                'seller_id'       => $seller->id,
                'cash_session_id' => $cashSession->id,
                'amount'          => $net,
                'category'        => 'Personal/Sueldos',
                'description'     => "Pago planilla #{$payroll->id} a {$payroll->staff->name} (Periodo: {$payroll->period_start->format('d/m')} al {$payroll->period_end->format('d/m')})",
            ]);
            $cashSession->increment('total_expenses', $net);
        }

        return back()->with('success', "Pago de nómina registrado correctamente para {$payroll->staff->name}. Egreso cargado en caja.");
    }

    public function payrollPdf($id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store || !$store->hasPremiumPackage()) {
            return redirect()->route('seller.pricing')->with('error', 'La gestión de planillas sólo está disponible en el plan Premium.');
        }
        $payroll = PosStaffPayroll::where('seller_id', $seller->id)->with('staff')->findOrFail($id);

        $pageTitle = 'Boleta de Pago #' . $payroll->id;
        
        return view('seller.pos.hr.payroll_pdf', compact('payroll', 'store', 'pageTitle'));
    }

    // ── Caja y Bancos ──
    public function bankAccounts()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Caja y Bancos';

        $accounts = PosBankAccount::where('seller_id', $seller->id)->latest()->get();
        
        $accountTransactions = PosTransaction::where('seller_id', $seller->id)
            ->whereNotNull('pos_bank_account_id')
            ->with('bankAccount', 'order')
            ->latest()
            ->paginate(25);

        return view('seller.pos.bank_accounts', compact('pageTitle', 'seller', 'store', 'accounts', 'accountTransactions'));
    }

    public function bankAccountStore(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|string|in:bank,wallet,pos_card',
            'account_number' => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:50',
        ]);

        PosBankAccount::create([
            'seller_id'      => $seller->id,
            'name'           => $request->name,
            'type'           => $request->type,
            'account_number' => $request->account_number,
            'bank_name'      => $request->bank_name,
            'status'         => 'active',
        ]);

        return back()->with('success', 'Cuenta / Monedero registrado con éxito.');
    }

    public function bankAccountUpdate(Request $request, $id)
    {
        $seller = $this->seller();
        $account = PosBankAccount::where('seller_id', $seller->id)->findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|string|in:bank,wallet,pos_card',
            'account_number' => 'nullable|string|max:50',
            'bank_name'      => 'nullable|string|max:50',
            'status'         => 'required|string|in:active,inactive',
        ]);

        $account->update([
            'name'           => $request->name,
            'type'           => $request->type,
            'account_number' => $request->account_number,
            'bank_name'      => $request->bank_name,
            'status'         => $request->status,
        ]);

        return back()->with('success', 'Cuenta / Monedero actualizado con éxito.');
    }

    public function bankAccountDelete($id)
    {
        $seller = $this->seller();
        $account = PosBankAccount::where('seller_id', $seller->id)->findOrFail($id);

        if ($account->transactions()->exists()) {
            $account->update(['status' => 'inactive']);
            return back()->with('success', 'La cuenta tiene movimientos. Se ha marcado como Inactiva.');
        }

        $account->delete();
        return back()->with('success', 'Cuenta / Monedero eliminado con éxito.');
    }

    public function bankAccountTransaction(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'pos_bank_account_id' => 'required|integer',
            'type'                => 'required|string|in:cash_in,cash_out',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string|max:200',
        ]);

        $account = PosBankAccount::where('seller_id', $seller->id)->active()->findOrFail($request->pos_bank_account_id);
        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();

        PosTransaction::create([
            'cash_session_id'     => $cashSession?->id,
            'seller_id'           => $seller->id,
            'pos_bank_account_id' => $account->id,
            'type'                => $request->type,
            'amount'              => $request->amount,
            'description'         => $request->description,
            'payment_method'      => $account->type === 'wallet' ? 'yape' : ($account->type === 'pos_card' ? 'card' : 'transfer'),
        ]);

        return back()->with('success', 'Movimiento de cuenta registrado correctamente.');
    }

    // ── Delivery Request (Free functionality for registered sellers) ──

    public function deliveryRequestForm()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Solicitar Envío';

        // Check active online drivers
        $activeDrivers = \App\Models\Driver::where('status', \App\Constants\Status::ENABLE)
            ->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])
            ->count();

        $drivers = \App\Models\Driver::where('status', \App\Constants\Status::ENABLE)
            ->whereIn('service_type', ['delivery', 'both'])
            ->orderBy('firstname')
            ->get();

        // Pusher/Reverb config for frontend real-time subscription
        $pusherConfig = [
            'key'     => env('PUSHER_APP_KEY', env('REVERB_APP_KEY', '')),
            'host'    => trim(env('REVERB_HOST', env('PUSHER_HOST', 'localhost')), '"'),
            'port'    => env('REVERB_PORT', env('PUSHER_PORT', 8080)),
            'scheme'  => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')),
            'cluster' => env('PUSHER_APP_CLUSTER', ''),
        ];

        // Fetch recent delivery requests created by this seller
        $recentFavors = \App\Models\Favor::where('seller_id', $seller->id)
            ->latest()
            ->take(15)
            ->with('courier')
            ->get();

        return view('seller.delivery_request', compact(
            'pageTitle', 'seller', 'store', 'activeDrivers', 'drivers', 'pusherConfig', 'recentFavors'
        ));
    }

    public function deliveryRequestFeeCalculate(Request $request)
    {
        $store = $this->store();
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró una tienda asociada.'], 404);
        }

        $pickupLat = (float) ($request->pickup_lat ?? $store->latitude);
        $pickupLng = (float) ($request->pickup_lng ?? $store->longitude);
        [$deliveryLat, $deliveryLng] = \App\Support\DeliveryPricing::deliveryCoordinatesFromRequest($request);

        $points = [];
        if ($pickupLat && $pickupLng) {
            $points[] = [$pickupLat, $pickupLng];
        }

        if ($request->has('stop_lat') && is_array($request->stop_lat)) {
            foreach ($request->stop_lat as $k => $sLat) {
                $sLng = $request->stop_lng[$k] ?? null;
                if ($sLat !== null && $sLng !== null && is_numeric($sLat) && is_numeric($sLng)) {
                    $points[] = [(float) $sLat, (float) $sLng];
                }
            }
        }

        if ($deliveryLat && $deliveryLng) {
            $points[] = [$deliveryLat, $deliveryLng];
        }

        $estimate = \App\Support\DeliveryPricing::estimateForMultiStops($points);
        return response()->json(array_merge(['status' => 'success'], $estimate));
    }

    public function deliveryRequestSubmit(Request $request)
    {
        $request->validate([
            'delivery_address'  => 'required|string|max:500',
            'delivery_lat'      => 'required|numeric',
            'delivery_lng'      => 'required|numeric',
            'description'       => 'required|string|max:1000',
            'recipient_name'    => 'nullable|string|max:200',
            'recipient_phone'   => 'nullable|string|max:20',
            'driver_id'         => 'nullable|string',
            'pickup_address'    => 'nullable|string|max:500',
            'pickup_lat'        => 'nullable|numeric',
            'pickup_lng'        => 'nullable|numeric',
            'payer_type'        => 'nullable|string|in:sender,recipient',
            'payment_method'    => 'nullable|string',
            // Sprint 2: Package details
            'package_weight_kg' => 'nullable|numeric|min:0|max:100',
            'package_dimensions'=> 'nullable|string|max:50',
            'is_fragile'        => 'nullable|boolean',
            'item_value'        => 'nullable|numeric|min:0',
            // Sprint 2: Time slot
            'scheduled_at'      => 'nullable|date|after:now',
            'time_slot'         => 'nullable|string|max:30',
            // Sprint 3: Express
            'is_express'        => 'nullable|boolean',
            // Sprint 4: New enterprise fields
            'shipment_type'     => 'nullable|string|in:document,food,package,pharmacy,grocery,other',
            'evidence_type'     => 'nullable|string|in:photo,pin,both',
            'cod_amount'        => 'nullable|numeric|min:0',
            'is_heavy'          => 'nullable|boolean',
            'is_temperature_controlled' => 'nullable|boolean',
            // Multi-stop support
            'stop_address'      => 'nullable|array|max:4',
            'stop_address.*'    => 'nullable|string|max:500',
            'stop_lat'          => 'nullable|array',
            'stop_lat.*'        => 'nullable|numeric',
            'stop_lng'          => 'nullable|array',
            'stop_lng.*'        => 'nullable|numeric',
        ]);

        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró la tienda asociada a tu cuenta.');
        }

        // Use custom pickup if provided, else store defaults
        $pickupAddress = $request->pickup_address ?? $store->address;
        $pickupLat     = $request->filled('pickup_lat') ? (float) $request->pickup_lat : (float) $store->latitude;
        $pickupLng     = $request->filled('pickup_lng') ? (float) $request->pickup_lng : (float) $store->longitude;

        [$deliveryLat, $deliveryLng] = \App\Support\DeliveryPricing::deliveryCoordinatesFromRequest($request);

        $stopsData = [];
        $points = [];
        if ($pickupLat && $pickupLng) {
            $points[] = [$pickupLat, $pickupLng];
        }

        if ($request->has('stop_address') && is_array($request->stop_address)) {
            foreach ($request->stop_address as $idx => $sAddr) {
                if (!empty($sAddr)) {
                    $sLat = isset($request->stop_lat[$idx]) ? (float) $request->stop_lat[$idx] : null;
                    $sLng = isset($request->stop_lng[$idx]) ? (float) $request->stop_lng[$idx] : null;
                    $sName = $request->stop_recipient_name[$idx] ?? ('Parada #' . ($idx + 1));
                    $sPhone = $request->stop_recipient_phone[$idx] ?? null;
                    $sNote = $request->stop_note[$idx] ?? null;

                    $stopsData[] = [
                        'stop_number'     => $idx + 1,
                        'address'         => $sAddr,
                        'lat'             => $sLat,
                        'lng'             => $sLng,
                        'recipient_name'  => $sName,
                        'recipient_phone' => $sPhone,
                        'note'            => $sNote,
                    ];

                    if ($sLat && $sLng) {
                        $points[] = [$sLat, $sLng];
                    }
                }
            }
        }

        if ($deliveryLat && $deliveryLng) {
            $points[] = [$deliveryLat, $deliveryLng];
        }

        $estimate = \App\Support\DeliveryPricing::estimateForMultiStops($points);
        $deliveryFee = $estimate['delivery_fee'] ?? 5;

        // Sprint 3: Express multiplier
        $isExpress = $request->boolean('is_express', false);
        if ($isExpress) {
            $deliveryFee = round($deliveryFee * 1.5, 1);
        }

        $total = $deliveryFee;
        $orderNo = 'ENV-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(5));

        // Generate PIN code for delivery verification
        $pinCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $driverId = $request->driver_id;
        $assignedDriver = null;
        $dispatchMode = 'manual';

        if ($driverId && $driverId !== 'all') {
            // Manual assignment
            $assignedDriver = \App\Models\Driver::where('status', \App\Constants\Status::ENABLE)
                ->whereIn('service_type', ['delivery', 'both'])
                ->findOrFail($driverId);
        } else {
            // Sprint 1: Auto-dispatch to nearest courier
            $dispatchMode = 'auto';
        }

        // Payment info
        $payerType = $request->payer_type ?? 'sender';
        $paymentMethodCode = $payerType === 'recipient' ? 'cash' : ($request->payment_method ?? 'cash');
        $paymentMethodName = match($paymentMethodCode) {
            'yape'   => 'Yape',
            'plin'   => 'Plin',
            'card'   => 'Tarjeta',
            default  => 'Efectivo',
        };
        $paymentStatus = $payerType === 'sender' ? 1 : 0;

        // Sprint 2: Scheduled delivery
        $scheduledAt = $request->input('scheduled_at');
        $timeSlot = $request->input('time_slot');

        // Sprint 3: Priority level based on express
        $priorityLevel = $isExpress ? 1 : 0;

        $favor = \App\Models\Favor::create([
            'order_no'             => $orderNo,
            'user_id'              => null,
            'seller_id'            => $seller->id,
            'source_type'          => 'seller',
            'type'                 => 'send',
            'description'          => $request->description,
            'store_name'           => $store->name,
            'store_address'        => $store->address,
            'estimated_amount'     => 0,
            'pickup_address'       => $pickupAddress,
            'pickup_lat'           => $pickupLat,
            'pickup_lng'           => $pickupLng,
            'delivery_address'     => $request->delivery_address,
            'delivery_lat'         => $deliveryLat,
            'delivery_lng'         => $deliveryLng,
            'stops'                => !empty($stopsData) ? $stopsData : null,
            'recipient_name'       => $request->recipient_name ?? 'Cliente',
            'recipient_phone'      => $request->recipient_phone,
            'delivery_fee'         => $deliveryFee,
            'total'                => $total,
            'status'               => $assignedDriver ? 'accepted' : 'searching_courier',
            'courier_id'           => $assignedDriver ? $assignedDriver->id : null,
            'courier_assigned_at'  => $assignedDriver ? now() : null,
            'payment_method_code'  => $paymentMethodCode,
            'payment_method_name'  => $paymentMethodName,
            'payment_status'       => $paymentStatus,
            'payer_type'           => $payerType,
            'pin_code'             => $pinCode,
            // Sprint 1: Auto dispatch
            'dispatch_mode'        => $dispatchMode,
            // Sprint 2: Package details
            'package_weight_kg'    => $request->input('package_weight_kg'),
            'package_dimensions'   => $request->input('package_dimensions'),
            'is_fragile'           => $request->boolean('is_fragile', false),
            'item_value'           => $request->input('item_value'),
            // Sprint 2: Time slot
            'scheduled_at'         => $scheduledAt,
            'time_slot'            => $timeSlot,
            // Sprint 3: Express
            'is_express'           => $isExpress,
            'priority_level'       => $priorityLevel,
            // Sprint 4: New enterprise fields
            'shipment_type'        => $request->input('shipment_type', 'document'),
            'evidence_type'        => $request->input('evidence_type', 'photo'),
            'cod_amount'           => $payerType === 'recipient' ? ($request->filled('cod_amount') && (float)$request->input('cod_amount') > 0 ? (float)$request->input('cod_amount') : (float)$total) : null,
            'is_heavy'             => $request->boolean('is_heavy', false),
            'is_temperature_controlled' => $request->boolean('is_temperature_controlled', false),
        ]);

        // Create PIN record
        $favor->pin()->create(['pin_code' => $pinCode]);

        // Sprint 1: Auto-dispatch if no manual driver assigned
        $autoAssigned = null;
        if (!$assignedDriver && $dispatchMode === 'auto') {
            try {
                $autoAssigned = \App\Services\AutoDispatchService::dispatchFavor($favor);
                if ($autoAssigned) {
                    $favor->refresh();
                }
            } catch (\Throwable $e) {
                // Fallback to broadcast to all couriers
            }
        }

        // FCM notifications (fallback if auto-dispatch didn't assign anyone)
        if (!$favor->courier_id) {
            try {
                if (gs('pn') && gs('firebase_config')) {
                    \App\Services\FcmService::sendToAllCouriers(
                        'Nuevo envío disponible',
                        ($isExpress ? '⚡ EXPRESS — ' : '') . 'Recoger en ' . $store->name . ' — S/ ' . number_format($deliveryFee, 2),
                        [
                            'type'    => 'new_delivery_request',
                            'favor_id' => (string) $favor->id,
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]
                    );
                }
            } catch (\Throwable $e) {}
        }

        // Calculate initial ETA
        try {
            $eta = \App\Services\EtaService::getTotalEstimate($favor);
            if ($eta) {
                $favor->update([
                    'estimated_minutes' => (int) $eta['total_estimate_min'],
                    'eta_updated_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status'    => 'success',
                'favor_id'  => $favor->id,
                'order_no'  => $orderNo,
                'pin_code'  => $pinCode,
                'eta'       => $eta ?? null,
                'auto_assigned' => $autoAssigned ? true : false,
                'message'   => 'Solicitud creada con éxito.',
            ]);
        }

        return back()->with('success', 'Solicitud #' . $orderNo . ' creada. PIN: ' . $pinCode);
    }

    public function deliveryRequestStatus($id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró tienda.'], 404);
        }

        $favor = \App\Models\Favor::with('courier')->findOrFail($id);

        // Security check: ensure the favor belongs to this seller
        if ((int) $favor->seller_id !== (int) $seller->id) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        $courier = null;
        if ($favor->courier) {
            $driverLat = $favor->courier->current_lat ? (float) $favor->courier->current_lat : 0;
            $driverLng = $favor->courier->current_lot ? (float) $favor->courier->current_lot : 0;
            $pickupLat = (float) ($favor->pickup_lat ?: $store->latitude);
            $pickupLng = (float) ($favor->pickup_lng ?: $store->longitude);

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
                'name'        => $favor->courier->fullname,
                'phone'       => $favor->courier->mobileNumber,
                'image'       => $favor->courier->image_src,
                'distance_km' => $distanceKm,
                'time_min'    => $timeMin,
                'current_lat' => $favor->courier->current_lat ? (float) $favor->courier->current_lat : null,
                'current_lng' => $favor->courier->current_lot ? (float) $favor->courier->current_lot : null,
                'bearing'     => null,
            ];
        }

        // Sprint 1: Calculate dynamic ETA
        $eta = null;
        if ($favor->courier_id) {
            try {
                $eta = \App\Services\EtaService::forFavor($favor);
                if ($eta) {
                    $favor->update([
                        'estimated_minutes' => (int) $eta['duration_min'],
                        'eta_updated_at'    => now(),
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        // Sprint 2: Get delivery confirmation requirements
        $confirmationRequirements = null;
        if (in_array($favor->status, ['at_pickup', 'on_way_to_delivery'])) {
            try {
                $confirmationRequirements = \App\Services\DeliveryConfirmationService::getRequirements($favor, 'favor');
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'status'       => 'success',
            'favor_status' => $favor->status,
            'favor'        => [
                'id'               => $favor->id,
                'pickup_address'   => $favor->pickup_address,
                'pickup_lat'       => (float) $favor->pickup_lat,
                'pickup_lng'       => (float) $favor->pickup_lng,
                'delivery_address' => $favor->delivery_address,
                'delivery_lat'     => (float) $favor->delivery_lat,
                'delivery_lng'     => (float) $favor->delivery_lng,
                'stops'            => $favor->stops,
                'status'           => $favor->status,
                'payer_type'       => $favor->payer_type,
                'payment_method_name' => $favor->payment_method_name,
                'pin_code'         => $favor->pin_code,
                // Sprint 1: ETA
                'estimated_minutes' => $favor->estimated_minutes,
                'eta'              => $eta,
                // Sprint 1: Delivery confirmation
                'confirmation_requirements' => $confirmationRequirements,
                // Sprint 2: Package details
                'package_weight_kg' => $favor->package_weight_kg,
                'is_fragile'       => $favor->is_fragile,
                'item_value'       => $favor->item_value,
                // Sprint 2: Time slot
                'scheduled_at'     => $favor->scheduled_at?->toISOString(),
                'time_slot'        => $favor->time_slot,
                // Sprint 3: Express
                'is_express'       => $favor->is_express,
                'priority_level'   => $favor->priority_level,
                // Sprint 4: Enterprise fields
                'shipment_type'    => $favor->shipment_type,
                'evidence_type'    => $favor->evidence_type,
                'cod_amount'       => $favor->cod_amount,
                'is_heavy'         => $favor->is_heavy,
                'is_temperature_controlled' => $favor->is_temperature_controlled,
            ],
            'courier' => $courier,
            'eta'     => $eta,
        ]);
    }

    public function deliveryOrderStatus($id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró tienda.'], 404);
        }

        $order = \App\Models\DeliveryOrder::with('driver')->where('store_id', $store->id)->findOrFail($id);

        $courier = null;
        if ($order->driver) {
            $driverLat = $order->driver->current_lat ? (float) $order->driver->current_lat : 0;
            $driverLng = $order->driver->current_lot ? (float) $order->driver->current_lot : 0;
            $pickupLat = (float) ($order->pickup_lat ?: $store->latitude);
            $pickupLng = (float) ($order->pickup_lng ?: $store->longitude);

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
                'name'        => $order->driver->firstname . ' ' . $order->driver->lastname,
                'phone'       => $order->driver->mobile,
                'image'       => $order->driver->image ? asset('storage/' . $order->driver->image) : null,
                'distance_km' => $distanceKm,
                'time_min'    => $timeMin,
                'current_lat' => $order->driver->current_lat ? (float) $order->driver->current_lat : null,
                'current_lng' => $order->driver->current_lot ? (float) $order->driver->current_lot : null,
            ];
        }

        return response()->json([
            'status'        => 'success',
            'order_status'  => $order->status,
            'order'         => [
                'id'               => $order->id,
                'order_no'         => $order->order_no,
                'status'           => $order->status,
                'pickup_lat'       => (float) ($order->pickup_lat ?? $store->latitude ?? 0),
                'pickup_lng'       => (float) ($order->pickup_lng ?? $store->longitude ?? 0),
                'pickup_address'   => $order->pickup_address ?? $store->address ?? '',
                'delivery_lat'     => (float) ($order->delivery_lat ?? 0),
                'delivery_lng'     => (float) ($order->delivery_lng ?? 0),
                'delivery_address' => $order->delivery_address ?? '',
                'pin_code'         => $order->pin_code,
                'total'            => (float) $order->total,
                'payment_status'   => $order->payment_status,
                'evidence_type'    => $order->evidence_type,
            ],
            'courier' => $courier,
        ]);
    }

    public function cancelFavor(Request $request, $id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró tienda.');
        }

        $favor = \App\Models\Favor::findOrFail($id);

        // Security check: ensure the favor belongs to this seller
        if ((int) $favor->seller_id !== (int) $seller->id) {
            abort(403, 'No autorizado');
        }

        if (in_array($favor->status, ['delivered', 'cancelled'])) {
            return back()->with('error', 'No se puede cancelar un envío entregado o ya cancelado.');
        }

        // Sprint 2: Structured cancellation reasons
        $cancelReasonCode = $request->input('cancel_reason_code');
        $validReasons = [
            'no_courier_available', 'wrong_address', 'changed_mind',
            'too_expensive', 'courier_far', 'duplicate_order', 'other',
        ];

        $favor->update([
            'status'            => 'cancelled',
            'cancelled_at'      => now(),
            'cancelled_by'      => 'seller',
            'cancel_reason_code' => in_array($cancelReasonCode, $validReasons) ? $cancelReasonCode : 'other',
        ]);

        // Broadcast status update
        event(new \App\Events\FavorStatusUpdated($favor));

        return back()->with('success', 'Envío #' . $favor->order_no . ' cancelado con éxito.');
    }

    public function requestReturn(Request $request, $id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró tienda.');
        }

        $favor = \App\Models\Favor::findOrFail($id);

        // Security check
        if ((int) $favor->seller_id !== (int) $seller->id) {
            abort(403, 'No autorizado');
        }

        $reasonCode = $request->input('return_reason_code');
        $notes = $request->input('return_notes');

        $result = \App\Services\ReturnHandlingService::requestReturn($favor, $reasonCode, $notes);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function favorStatus(Request $request, $id)
    {
        $seller = $this->seller();
        $store = $this->store();
        if (!$store) {
            return back()->with('error', 'No se encontró tienda.');
        }

        $favor = \App\Models\Favor::findOrFail($id);

        // Security check: ensure the favor belongs to this seller
        if ((int) $favor->seller_id !== (int) $seller->id) {
            abort(403, 'No autorizado');
        }

        $newStatus = $request->status;
        $allowedTransitions = [
            'searching_courier' => ['accepted', 'cancelled'],
            'accepted'          => ['on_way_to_pickup', 'cancelled'],
            'on_way_to_pickup'  => ['at_pickup', 'cancelled'],
            'at_pickup'         => ['on_way_to_delivery', 'cancelled'],
            'on_way_to_delivery'=> ['delivered', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$favor->status]) || !in_array($newStatus, $allowedTransitions[$favor->status])) {
            return back()->with('error', 'No puedes cambiar a este estado desde ' . $favor->status);
        }

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'cancelled') {
            $updateData['cancelled_at'] = now();
        } elseif ($newStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        $favor->update($updateData);

        event(new \App\Events\FavorStatusUpdated($favor));

        if ($favor->user) {
            $statusLabels = [
                'accepted'           => ['Repartidor asignado', 'Tu envío #' . $favor->order_no . ' ha sido asignado a un repartidor'],
                'on_way_to_pickup'   => ['Repartidor en camino', 'El repartidor va rumbo a recoger tu envío #' . $favor->order_no],
                'at_pickup'          => ['Recogiendo tu envío', 'El repartidor está en el punto de recogida del envío #' . $favor->order_no],
                'on_way_to_delivery' => ['Envío en camino', 'Tu envío #' . $favor->order_no . ' está en camino a la entrega'],
                'delivered'          => ['Envío entregado', 'Tu envío #' . $favor->order_no . ' ha sido entregado exitosamente'],
                'cancelled'          => ['Envío cancelado', 'Tu envío #' . $favor->order_no . ' ha sido cancelado'],
            ];
            if (isset($statusLabels[$newStatus])) {
                \App\Services\FcmService::sendToUser($favor->user, $statusLabels[$newStatus][0], $statusLabels[$newStatus][1], [
                    'job_id' => (string) $favor->id, 'order_no' => $favor->order_no, 'type' => 'favor_status', 'status' => $newStatus,
                ]);
            }
        }

        return back()->with('success', 'Estado del envío actualizado a ' . $newStatus);
    }



    // ── Table Management Features: Transfer, Grouping & Reservations ──

    public function transferTable(Request $request)
    {
        $request->validate([
            'from_table_id' => 'required|exists:pos_tables,id',
            'to_table_id'   => 'required|exists:pos_tables,id',
        ]);

        $seller = $this->seller();
        $fromTable = PosTable::where('seller_id', $seller->id)->findOrFail($request->from_table_id);
        $toTable = PosTable::where('seller_id', $seller->id)->findOrFail($request->to_table_id);

        if ($toTable->status !== 'free') {
            return back()->with('error', 'La mesa de destino no está libre.');
        }

        // Find active order of fromTable
        $order = PosOrder::where('seller_id', $seller->id)
            ->where('pos_table_id', $fromTable->id)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->latest()
            ->first();

        if (!$order) {
            return back()->with('error', 'No hay comanda activa en la mesa de origen.');
        }

        // Transfer order to destination table
        $order->update(['pos_table_id' => $toTable->id]);

        // Move active table statuses
        $fromTable->update(['status' => 'free']);
        $toTable->update(['status' => 'occupied']);

        // Update linked tables as well: if there were tables linked to fromTable, link them to toTable
        PosTable::where('linked_to_table_id', $fromTable->id)->update([
            'linked_to_table_id' => $toTable->id
        ]);

        return back()->with('success', 'Comanda transferida de ' . $fromTable->name . ' a ' . $toTable->name);
    }

    public function groupTables(Request $request)
    {
        $request->validate([
            'primary_table_id'     => 'required|exists:pos_tables,id',
            'secondary_table_ids'  => 'required|array|min:1',
            'secondary_table_ids.*' => 'exists:pos_tables,id',
        ]);

        $seller = $this->seller();
        $primary = PosTable::where('seller_id', $seller->id)->findOrFail($request->primary_table_id);
        $grouped = [];

        foreach ($request->secondary_table_ids as $sid) {
            $secondary = PosTable::where('seller_id', $seller->id)->findOrFail($sid);

            if ($secondary->id === $primary->id) continue;
            if ($secondary->status !== 'free') continue;

            $secondary->update([
                'linked_to_table_id' => $primary->id,
                'status' => 'occupied',
            ]);
            $grouped[] = $secondary->name;
        }

        if (empty($grouped)) {
            return back()->with('error', 'No se pudieron agrupar mesas. Verifica que estén libres.');
        }

        return back()->with('success', 'Mesa(s) ' . implode(', ', $grouped) . ' agrupada(s) con ' . $primary->name);
    }

    public function ungroupTable(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:pos_tables,id',
        ]);

        $seller = $this->seller();
        $table = PosTable::where('seller_id', $seller->id)->findOrFail($request->table_id);

        if (!$table->linked_to_table_id) {
            return back()->with('error', 'Esta mesa no está agrupada.');
        }

        $table->update([
            'linked_to_table_id' => null,
            'status' => 'free',
        ]);

        return back()->with('success', 'Mesa ' . $table->name . ' desagrupada.');
    }

    public function ungroupAll(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:pos_tables,id',
        ]);

        $seller = $this->seller();
        $table = PosTable::where('seller_id', $seller->id)->findOrFail($request->table_id);

        $primaryId = $table->linked_to_table_id ?: $table->id;

        PosTable::where('seller_id', $seller->id)
            ->where('linked_to_table_id', $primaryId)
            ->update(['linked_to_table_id' => null, 'status' => 'free']);

        if ($table->id === $primaryId) {
            $table->update(['status' => 'free']);
        }

        return back()->with('success', 'Todas las mesas del grupo han sido desagrupadas.');
    }

    public function reservationsList()
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Reservas de Mesas';

        $reservations = \App\Models\PosTableReservation::where('seller_id', $seller->id)
            ->with('table.area')
            ->orderBy('reservation_time', 'asc')
            ->get();

        $tables = PosTable::where('seller_id', $seller->id)->with('area')->orderBy('name')->get();
        $products = \App\Models\Product::where('store_id', $store?->id)->where('status', 1)->orderBy('name')->get();

        return view('seller.pos.reservations', compact('pageTitle', 'seller', 'store', 'reservations', 'tables', 'products'));
    }

    public function reservationSave(Request $request)
    {
        $request->validate([
            'id'               => 'nullable|exists:pos_table_reservations,id',
            'pos_table_id'     => 'nullable|exists:pos_tables,id',
            'customer_name'    => 'required|string|max:100',
            'customer_phone'   => 'nullable|string|max:20',
            'reservation_time' => 'required',
            'guests_count'     => 'required|integer|min:1',
            'notes'            => 'nullable|string|max:500',
        ]);

        $seller = $this->seller();
        $store = $this->store();

        $dishes = [];
        if ($request->dishes && is_array($request->dishes)) {
            foreach ($request->dishes as $productId => $dishData) {
                if (isset($dishData['selected']) && $dishData['selected'] == 1) {
                    $dishes[] = [
                        'product_id' => (int) $productId,
                        'quantity' => (int) ($dishData['quantity'] ?? 1)
                    ];
                }
            }
        }

        $data = [
            'seller_id'        => $seller->id,
            'store_id'         => $store?->id,
            'pos_table_id'     => $request->pos_table_id ?: null,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'reservation_time' => $request->reservation_time,
            'guests_count'     => $request->guests_count,
            'notes'            => $request->notes,
            'dishes'           => $dishes,
        ];

        // Double booking detection
        if (!empty($data['pos_table_id']) && !empty($data['reservation_time'])) {
            $resTime = \Carbon\Carbon::parse($data['reservation_time']);
            $windowStart = $resTime->copy()->subMinutes(90);
            $windowEnd = $resTime->copy()->addMinutes(90);

            $conflictQuery = \App\Models\PosTableReservation::where('seller_id', $seller->id)
                ->where('pos_table_id', $data['pos_table_id'])
                ->where('reservation_time', '>=', $windowStart)
                ->where('reservation_time', '<=', $windowEnd)
                ->whereNotIn('status', ['cancelled']);

            if ($request->id) {
                $conflictQuery->where('id', '!=', $request->id);
            }

            if ($conflictQuery->exists()) {
                return back()->with('error', 'La mesa ya está reservada en ese horario (±90 minutos). Elige otra mesa o horario.');
            }
        }

        if ($request->id) {
            $res = \App\Models\PosTableReservation::where('seller_id', $seller->id)->findOrFail($request->id);
            $res->update($data);
            $msg = 'Reservación actualizada';
        } else {
            \App\Models\PosTableReservation::create($data);
            $msg = 'Reservación programada';
        }

        return back()->with('success', $msg);
    }

    public function reservationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,seated,cancelled'
        ]);

        $seller = $this->seller();
        $reservation = \App\Models\PosTableReservation::where('seller_id', $seller->id)->findOrFail($id);
        $reservation->update(['status' => $request->status]);

        // If seated, we can automatically mark the table as occupied in the POS
        if ($request->status === 'seated' && $reservation->pos_table_id) {
            PosTable::where('id', $reservation->pos_table_id)->update(['status' => 'occupied']);
        }

        return back()->with('success', 'Estado de reservación actualizado');
    }

    public function crmCustomers(Request $request)
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'CRM Clientes';

        // Query unique customers from pos_orders. Prefer document, but keep
        // phone-only customers visible in the CRM as well.
        $query = PosOrder::where('seller_id', $seller->id)
            ->where(function($q) {
                $q->where(function($docQuery) {
                    $docQuery->whereNotNull('customer_doc')
                        ->where('customer_doc', '!=', '');
                })->orWhere(function($phoneQuery) {
                    $phoneQuery->whereNotNull('customer_phone')
                        ->where('customer_phone', '!=', '');
                });
            });

        // Search filter
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', $search)
                  ->orWhere('customer_doc', 'LIKE', $search)
                  ->orWhere('customer_phone', 'LIKE', $search);
            });
        }

        $customerRows = $query->selectRaw(
            "id,
            total,
            paid_at,
            created_at,
            customer_doc,
            customer_doc_type,
            customer_phone,
            customer_name,
            COALESCE(NULLIF(customer_doc, ''), NULLIF(customer_phone, '')) as customer_key"
        );

        // We group by the best available customer identifier and calculate stats.
        // The derived table keeps MySQL ONLY_FULL_GROUP_BY happy during pagination.
        $customers = \Illuminate\Support\Facades\DB::query()
            ->fromSub($customerRows, 'customer_rows')
            ->selectRaw(
                "customer_key,
                MAX(NULLIF(customer_doc, '')) as customer_doc,
                MAX(customer_doc_type) as customer_doc_type,
                MAX(NULLIF(customer_phone, '')) as customer_phone,
                MAX(NULLIF(customer_name, '')) as customer_name,
                COUNT(id) as total_orders,
                SUM(total) as total_spent,
                MAX(COALESCE(paid_at, created_at)) as last_purchase_at"
            )
            ->groupBy('customer_key')
            ->orderBy('total_orders', 'desc')
            ->paginate(15);

        return view('seller.crm.customers', compact('pageTitle', 'seller', 'store', 'customers'));
    }

    public function crmCustomerProfile($doc)
    {
        $seller = $this->seller();
        $store = $this->store();
        $pageTitle = 'Perfil del Cliente';

        $customerFilter = function($query) use ($doc) {
            $query->where('customer_doc', $doc)
                ->orWhere(function($q) use ($doc) {
                    $q->where(function($docQuery) {
                        $docQuery->whereNull('customer_doc')
                            ->orWhere('customer_doc', '');
                    })->where('customer_phone', $doc);
                });
        };

        // Find customer details from their latest order
        $customer = PosOrder::where('seller_id', $seller->id)
            ->where($customerFilter)
            ->latest()
            ->firstOrFail();

        // Calculate totals
        $stats = PosOrder::where('seller_id', $seller->id)
            ->where($customerFilter)
            ->select(
                \Illuminate\Support\Facades\DB::raw('COUNT(id) as total_orders'),
                \Illuminate\Support\Facades\DB::raw('SUM(total) as total_spent'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN payment_status = "credit" THEN total ELSE 0 END) as total_credit'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN payment_status = "paid" THEN total ELSE 0 END) as total_paid')
            )
            ->first();

        // Get customer orders history
        $orders = PosOrder::where('seller_id', $seller->id)
            ->where($customerFilter)
            ->with('sunatInvoice')
            ->latest()
            ->paginate(10);

        return view('seller.crm.profile', compact('pageTitle', 'seller', 'store', 'customer', 'stats', 'orders'));
    }
}
