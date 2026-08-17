<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\BusinessPackage;
use App\Models\DeliveryOrder;
use App\Models\Store;
use App\Models\StorePackage;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    private function seller()
    {
        return auth()->user();
    }

    public function list()
    {
        $packages = BusinessPackage::active()->orderBy('sort_order')->get();

        return apiResponse('packages', 'success', ['Paquetes disponibles'], [
            'packages' => $packages,
        ]);
    }

    public function myPackages(Request $request)
    {
        $seller = $this->seller();

        $storeId = $request->store_id;
        $query = StorePackage::with('package')
            ->where('seller_id', $seller->id)
            ->latest();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $subscriptions = $query->get()->map(function ($sp) {
            return [
                'id'            => $sp->id,
                'store_id'      => $sp->store_id,
                'package'       => [
                    'id'       => $sp->package->id,
                    'name'     => $sp->package->name,
                    'slug'     => $sp->package->slug,
                    'type'     => $sp->package->type,
                    'icon'     => $sp->package->icon,
                    'features' => $sp->package->features,
                ],
                'status'        => $sp->status,
                'amount_paid'   => $sp->amount_paid,
                'payment_method'=> $sp->payment_method,
                'starts_at'     => $sp->starts_at,
                'expires_at'    => $sp->expires_at,
                'is_active'     => $sp->isActive(),
                'created_at'    => $sp->created_at,
            ];
        });

        return apiResponse('my_packages', 'success', ['Mis paquetes'], [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function purchase(Request $request)
    {
        $seller = $this->seller();

        $validator = Validator::make($request->all(), [
            'package_id'     => 'required|integer|exists:business_packages,id',
            'store_id'       => 'required|integer|exists:stores,id',
            'payment_method' => 'nullable|string',
            'payment_ref'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $pkg = BusinessPackage::active()->findOrFail($request->package_id);

        // Verify store belongs to seller
        $store = \App\Models\Store::where('id', $request->store_id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$store) {
            return apiResponse('invalid_store', 'error', ['La tienda no te pertenece']);
        }

        $subscription = StorePackage::create([
            'store_id'       => $request->store_id,
            'seller_id'      => $seller->id,
            'package_id'     => $pkg->id,
            'status'         => 'pending',
            'amount_paid'    => $pkg->price,
            'payment_method' => $request->payment_method ?? 'manual',
            'payment_ref'    => $request->payment_ref,
            'starts_at'      => null,
            'expires_at'     => null,
        ]);

        return apiResponse('purchase_success', 'success', ['Solicitud enviada. Te notificaremos cuando se active.'], [
            'subscription' => [
                'id'           => $subscription->id,
                'package_name' => $pkg->name,
                'amount_paid'  => $pkg->price,
                'status'       => 'pending',
            ],
        ]);
    }

    public function analytics(Request $request, $storeId)
    {
        $seller = $this->seller();
        $store = Store::where('id', $storeId)->where('seller_id', $seller->id)->firstOrFail();

        if (!$store->is_premium) {
            return apiResponse('not_premium', 'error', ['Requiere plan Premium para acceder a analytics']);
        }

        $totalOrders = DeliveryOrder::where('store_id', $store->id)->count();
        $completedOrders = DeliveryOrder::where('store_id', $store->id)->where('status', 'delivered')->count();
        $totalRevenue = DeliveryOrder::where('store_id', $store->id)->where('status', 'delivered')->sum('total');
        $avgOrderValue = $completedOrders > 0 ? round($totalRevenue / $completedOrders, 2) : 0;

        $ordersByDay = DeliveryOrder::where('store_id', $store->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = \App\Models\DeliveryOrderItem::whereHas('order', fn($q) => $q->where('store_id', $store->id))
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(total_price) as total_sales')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return apiResponse('analytics', 'success', ['Estadísticas de la tienda'], [
            'summary' => [
                'total_orders'    => $totalOrders,
                'completed_orders'=> $completedOrders,
                'total_revenue'   => round($totalRevenue, 2),
                'avg_order_value' => $avgOrderValue,
            ],
            'daily'       => $ordersByDay,
            'top_products'=> $topProducts,
        ]);
    }

    public function notify(Request $request, $storeId)
    {
        $seller = $this->seller();
        $store = Store::where('id', $storeId)->where('seller_id', $seller->id)->firstOrFail();

        if (!$store->is_premium) {
            return apiResponse('not_premium', 'error', ['Requiere plan Premium para enviar notificaciones']);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'body'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $userIds = DeliveryOrder::where('store_id', $store->id)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $count = 0;
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            FcmService::sendToUser($user, $request->title, $request->body, [
                'store_id' => (string) $store->id,
                'type'     => 'store_promotion',
            ]);
            $count++;
        }

        return apiResponse('notify_sent', 'success', ['Notificación enviada a ' . $count . ' clientes'], [
            'recipients' => $count,
        ]);
    }

    public function qrCode(Request $request, $storeId)
    {
        $seller = $this->seller();
        $store = Store::where('id', $storeId)->where('seller_id', $seller->id)->firstOrFail();

        $hasQR = $store->storePackages()
            ->where('status', 'active')
            ->whereHas('package', fn($q) => $q->where('type', 'qr'))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasQR) {
            return apiResponse('no_qr_package', 'error', ['Requiere plan QR Order para generar código QR']);
        }

        $storeUrl = route('delivery.store', $store);

        return apiResponse('qr_code', 'success', ['Código QR generado'], [
            'store_url'  => $storeUrl,
            'store_name' => $store->name,
            'qr_url'     => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($storeUrl),
        ]);
    }
}
