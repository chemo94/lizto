<?php

namespace App\Http\Controllers\Api;

use App\Models\KioskSession;
use App\Models\Seller;
use App\Models\SellerCompany;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductAddon;
use App\Models\DeliveryOrder;
use App\Models\PosTable;
use App\Models\PosArea;
use App\Models\Gateway;
use App\Events\NewDeliveryOrderPlaced;
use App\Services\FcmService;
use App\Support\DeliveryPricing;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KioskController extends Controller
{
    public function lookupRuc(Request $request)
    {
        $request->validate([
            'ruc' => 'required|string|size:11',
        ]);

        $ruc = $request->ruc;

        $company = SellerCompany::where('document_number', $ruc)->first();
        if (!$company) {
            $company = Seller::where('document_number', $ruc)->first();
        }

        $seller = null;
        if ($company instanceof SellerCompany) {
            $seller = $company->seller;
        } elseif ($company instanceof Seller) {
            $seller = $company;
        }

        if (!$seller) {
            return apiResponse('not_found', 'error', ['No se encontro vendedor con ese RUC']);
        }

        $stores = Store::where('seller_id', $seller->id)
            ->where('status', 1)
            ->get()
            ->map(fn($s) => [
                'id'    => $s->id,
                'name'  => $s->name,
                'image' => $s->image,
                'address' => $s->address,
            ]);

        if ($stores->isEmpty()) {
            return apiResponse('no_stores', 'error', ['Este vendedor no tiene tiendas activas']);
        }

        return apiResponse('success', 'success', [], [
            'seller_id'   => $seller->id,
            'seller_name' => $seller->name,
            'stores'      => $stores,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
            'pin'      => 'required|string|min:4|max:10',
        ]);

        $session = KioskSession::where('store_id', $request->store_id)
            ->where('pin_code', $request->pin)
            ->where('is_active', true)
            ->first();

        if (!$session) {
            return apiResponse('invalid_pin', 'error', ['PIN invalido o kiosco desactivado']);
        }

        $session->update([
            'last_login_at' => now(),
            'device_name'   => $request->header('User-Agent', 'kiosk'),
        ]);

        $seller = $session->seller;
        $token  = $seller->createToken('kiosk-token', ['seller'])->plainTextToken;

        return apiResponse('success', 'success', ['Login exitoso'], [
            'token'      => $token,
            'seller_id'  => $seller->id,
            'seller_name'=> $seller->name,
            'store_id'   => $session->store_id,
            'store_name' => $session->store->name ?? '',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return apiResponse('success', 'success', ['Sesion cerrada']);
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();
        return apiResponse('success', 'success', ['Token valido'], [
            'seller_id'  => $user->id,
            'seller_name'=> $user->name,
        ]);
    }

    public function menu(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        $categories = StoreCategory::where('store_id', $storeId)
            ->where('status', 1)
            ->with(['products' => function ($q) {
                $q->where('status', 1)
                  ->with([
                      'variations' => fn($v) => $v->where('status', 1)->orderBy('sort_order'),
                      'addons'     => fn($a) => $a->where('status', 1)->orderBy('sort_order'),
                  ])
                  ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return apiResponse('success', 'success', [], [
            'store'      => [
                'id'               => $store->id,
                'name'             => $store->name,
                'preparation_time' => $store->preparation_time ?? 30,
                'image'            => $store->image,
            ],
            'categories' => $categories,
        ]);
    }

    public function tables(Request $request, $storeId)
    {
        $areas = PosArea::where('seller_id', $request->user()->id)
            ->with(['tables' => function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn($area) => $area->tables->isNotEmpty());

        return apiResponse('success', 'success', [], ['areas' => $areas]);
    }

    public function paymentMethods(Request $request, $storeId)
    {
        $gateways = Gateway::active()->get()->map(fn($g) => [
            'id'    => $g->id,
            'code'  => $g->code,
            'name'  => $g->name,
            'image' => $g->image,
        ]);

        $methods = collect([['id' => 0, 'code' => 0, 'name' => 'Efectivo', 'image' => null]])
            ->merge($gateways)
            ->toArray();

        return apiResponse('success', 'success', [], ['payment_methods' => $methods]);
    }

    public function orderCreate(Request $request)
    {
        $request->validate([
            'store_id'       => 'required|integer',
            'order_type'     => 'required|in:delivery,takeaway,dine_in',
            'items'          => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'subtotal'       => 'required|numeric|min:0',
            'total'          => 'required|numeric|min:0',
        ]);

        $store = Store::findOrFail($request->store_id);
        $user  = $request->user();

        $orderItems = [];
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            $variationName  = null;
            $variationPrice = 0;
            if (!empty($item['variation_id'])) {
                $variation = ProductVariation::find($item['variation_id']);
                if ($variation) {
                    $variationName  = $variation->name;
                    $variationPrice = $variation->price;
                }
            }

            $selectedAddons = [];
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addonId) {
                    $addon = ProductAddon::find($addonId);
                    if ($addon) {
                        $selectedAddons[] = [
                            'addon_name'  => $addon->name,
                            'addon_price' => $addon->price,
                        ];
                    }
                }
            }

            $total = ($item['unit_price'] + $variationPrice) * $item['quantity'];

            $orderItems[] = [
                'data'      => [
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_image' => $product->image,
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $item['unit_price'],
                    'total_price'   => $total,
                    'notes'         => $item['notes'] ?? null,
                ],
                'variation' => $variationName ? [
                    'variation_name'  => $variationName,
                    'variation_price' => $variationPrice,
                ] : null,
                'addons'    => $selectedAddons,
            ];
        }

        $paymentMethodCode = 0;
        $paymentMethodName = 'Efectivo';
        if ($request->payment_method !== 'cash' && $request->payment_method !== 'efectivo') {
            $gateway = Gateway::where('code', $request->payment_method)->active()->first()
                    ?? Gateway::where('alias', $request->payment_method)->active()->first();
            if ($gateway) {
                $paymentMethodCode = $gateway->code;
                $paymentMethodName = $gateway->name;
            }
        }

        $deliveryAddress = $request->delivery_address ?? null;
        $deliveryLat     = $request->delivery_lat ?? null;
        $deliveryLng     = $request->delivery_lng ?? null;
        $deliveryFee     = 0;
        if ($request->order_type === 'delivery' && $deliveryLat && $deliveryLng) {
            $estimate    = DeliveryPricing::estimateForStore($store, $deliveryLat, $deliveryLng);
            $deliveryFee = $estimate['delivery_fee'] ?? 0;
        }

        $tableId = ($request->order_type === 'dine_in' && !empty($request->table_id))
            ? $request->table_id : null;

        $orderNo = 'KIO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $order = DeliveryOrder::create([
            'order_no'            => $orderNo,
            'user_id'             => $user->id,
            'store_id'            => $store->id,
            'subtotal'            => $request->subtotal,
            'delivery_fee'        => $deliveryFee,
            'discount'            => 0,
            'tip'                 => 0,
            'total'               => $request->total,
            'status'              => 'pending',
            'delivery_address'    => $deliveryAddress,
            'delivery_lat'        => $deliveryLat,
            'delivery_lng'        => $deliveryLng,
            'contact_phone'       => $request->contact_phone ?? null,
            'contact_name'        => $request->contact_name ?? null,
            'notes'               => $request->notes ?? null,
            'payment_method_code' => $paymentMethodCode,
            'payment_method_name' => $paymentMethodName,
            'cash_pay_amount'     => $request->cash_received ?? null,
        ]);

        foreach ($orderItems as $oi) {
            $item = $order->items()->create($oi['data']);
            if ($oi['variation']) {
                $item->variation()->create($oi['variation']);
            }
            foreach ($oi['addons'] as $addonData) {
                $item->addons()->create($addonData);
            }
        }

        $order->load('items.variation', 'items.addons', 'store');

        broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));

        if ($order->store && $order->store->seller) {
            FcmService::sendToSeller(
                $order->store->seller,
                'Nuevo pedido #' . $order->order_no,
                $order->store->name . ' - ' . $order->items->count() . ' productos por S/ ' . number_format($order->total, 2),
                ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
            );
        }

        return apiResponse('success', 'success', ['Pedido creado exitosamente'], [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'total'    => $order->total,
            'status'   => $order->status,
        ]);
    }

    public function ordersActive(Request $request, $storeId)
    {
        $orders = DeliveryOrder::where('store_id', $storeId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['cancelled', 'delivered'])
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return apiResponse('success', 'success', [], ['orders' => $orders]);
    }
}
