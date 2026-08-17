<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\NewDeliveryOrderPlaced;
use App\Models\Coupon;
use App\Models\DeliveryOrder;
use App\Models\DeliveryRefund;
use App\Models\DeliveryReview;
use App\Support\DeliveryPricing;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryOrderController extends Controller
{
    public function create(Request $request)
    {
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        $request->merge([
            'delivery_lat' => $deliveryLat,
            'delivery_lng' => $deliveryLng,
        ]);

        $validator = Validator::make($request->all(), [
            'store_id'         => 'required|exists:stores,id',
            'items'            => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.addon_ids'    => 'nullable|array',
            'items.*.addon_ids.*'  => 'exists:product_addons,id',
            'delivery_address'  => 'required|string',
            'delivery_lat'      => 'required|numeric',
            'delivery_lng'      => 'required|numeric',
            'contact_phone'     => 'required|string',
            'contact_name'      => 'required|string',
            'notes'             => 'nullable|string|max:500',
            'tip'               => 'nullable|numeric|min:0',
            'scheduled_time'    => 'nullable|date',
            'payment_method_code' => 'nullable|exists:gateways,code',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user  = auth()->user();
        $store = Store::active()->findOrFail($request->store_id);

        $subtotal   = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $product     = Product::active()->with('variations', 'addons')->findOrFail($item['product_id']);
            $unitPrice   = $product->finalPrice();
            $itemSubtotal = 0;

            // If a variation is selected, use its price instead
            $variationName  = null;
            $variationPrice = 0;
            if (!empty($item['variation_id'])) {
                $variation = $product->variations->find($item['variation_id']);
                if ($variation) {
                    $variationName  = $variation->name;
                    $variationPrice = $variation->price;
                    $unitPrice      = $variation->price;
                }
            }

            // Addons
            $selectedAddons = [];
            if (!empty($item['addon_ids'])) {
                $addons = $product->addons->whereIn('id', $item['addon_ids']);
                foreach ($addons as $addon) {
                    $selectedAddons[] = [
                        'addon_name'  => $addon->name,
                        'addon_price' => $addon->price,
                    ];
                    $unitPrice += $addon->price;
                }
            }

            $total = $unitPrice * $item['quantity'];
            $subtotal += $total;

            $orderItemData = [
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'product_image' => $product->image,
                'quantity'      => $item['quantity'],
                'unit_price'    => $unitPrice,
                'total_price'   => $total,
            ];

            $orderItems[] = [
                'data'       => $orderItemData,
                'variation'  => $variationName ? ['variation_name' => $variationName, 'variation_price' => $variationPrice] : null,
                'addons'     => $selectedAddons,
            ];
        }

        $estimate    = DeliveryPricing::estimateForStore($store, $deliveryLat, $deliveryLng);
        if (!$estimate['in_coverage']) {
            return apiResponse('outside_delivery_coverage', 'error', ['La direccion esta fuera de la zona de cobertura']);
        }

        $deliveryFee = $estimate['delivery_fee'];
        $tip         = $request->tip ?? 0;
        $total       = $subtotal + $deliveryFee + $tip;

        $paymentMethodCode = null;
        if ($request->payment_method_code) {
            $gateway = Gateway::where('code', $request->payment_method_code)->active()->first();
            if ($gateway) {
                $paymentMethodCode = $gateway->code;
            }
        }

        $orderNo = 'DEL-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(6));

        $order = DeliveryOrder::create([
            'order_no'           => $orderNo,
            'user_id'            => $user->id,
            'store_id'           => $store->id,
            'subtotal'           => $subtotal,
            'delivery_fee'       => $deliveryFee,
            'discount'           => 0,
            'tip'                => $tip,
            'total'              => $total,
            'status'             => 'pending',
            'delivery_address'   => $request->delivery_address,
            'delivery_lat'       => $deliveryLat,
            'delivery_lng'       => $deliveryLng,
            'contact_phone'      => $request->contact_phone,
            'contact_name'       => $request->contact_name,
            'notes'              => $request->notes,
            'scheduled_at'       => $request->scheduled_time,
            'payment_method_code' => $paymentMethodCode,
        ]);

        // Save user's delivery location for emergency/audit purposes
        if (!empty($deliveryLat) && !empty($deliveryLng)) {
            $user->update([
                'latitude'  => $deliveryLat,
                'longitude' => $deliveryLng,
            ]);
        }

        foreach ($orderItems as $oi) {
            $item = $order->items()->create($oi['data']);

            if ($oi['variation']) {
                $item->variation()->create($oi['variation']);
            }
            foreach ($oi['addons'] as $addonData) {
                $item->addons()->create($addonData);
            }
        }

        $order->load('items.variation', 'items.addons', 'store.seller');

        // Broadcast real-time notification to admin panel + store (WebSocket/Pusher)
        broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));

        // FCM push to the Seller app (mobile)
        if ($order->store?->seller) {
            FcmService::sendToSeller(
                $order->store->seller,
                '🛒 Nuevo pedido #' . $order->order_no,
                ($order->store->name ?? 'Delivery') . ' · ' . $order->items->count() . ' items · S/ ' . number_format($order->total, 2),
                ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
            );
        }

        // FCM push to the Admin panel
        FcmService::sendToAdmin(
            '🛒 Nuevo pedido #' . $order->order_no,
            ($order->store?->name ?? 'Delivery') . ' · S/ ' . number_format($order->total, 2),
            ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
        );

        return apiResponse('order_created', 'success', ['Pedido creado correctamente'], [
            'order'              => $order,
            'delivery_estimate'  => $estimate,
            'product_image_path' => 'storage',
            'store_image_path'   => getFilePath('store'),
        ]);
    }

    public function orders(Request $request)
    {
        $user   = auth()->user();
        $orders = DeliveryOrder::where('user_id', $user->id)
            ->with('items.variation', 'items.addons', 'store')
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('rider_orders', 'success', ['Tus pedidos'], [
            'orders'             => $orders,
            'product_image_path' => 'storage',
            'store_image_path'   => getFilePath('store'),
        ]);
    }

    public function detail($id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->with('items.variation', 'items.addons', 'store', 'driver')
            ->findOrFail($id);

        return apiResponse('order_detail', 'success', ['Detalle del pedido'], [
            'order'              => $order,
            'product_image_path' => 'storage',
            'store_image_path'   => getFilePath('store'),
            'driver_image_path'  => getFilePath('driver'),
            'driver' => $order->driver ? [
                'id'        => $order->driver->id,
                'name'      => $order->driver->firstname . ' ' . $order->driver->lastname,
                'phone'     => $order->driver->mobile,
                'image'     => $order->driver->image,
                'latitude'  => $order->driver->current_lat,
                'longitude' => $order->driver->current_lot,
            ] : null,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->findOrFail($id);

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason ?? 'Cancelado por el usuario',
        ]);

        return apiResponse('order_cancelled', 'success', ['Pedido cancelado']);
    }

    public function addTip(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'on_way'])
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tip' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $order->update([
            'tip'   => $request->tip,
            'total' => $order->subtotal + $order->delivery_fee + $request->tip,
        ]);

        return apiResponse('tip_updated', 'success', ['Propina actualizada'], [
            'order' => $order,
        ]);
    }

    public function review(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $review = DeliveryReview::updateOrCreate(
            ['reviewable_type' => DeliveryOrder::class, 'reviewable_id' => $order->id],
            [
                'user_id'    => $user->id,
                'courier_id' => $order->driver_id,
                'rating'     => $request->rating,
                'review'     => $request->review,
            ]
        );

        return apiResponse('review_saved', 'success', ['Calificación enviada'], ['review' => $review]);
    }

    public function paymentHistory(Request $request)
    {
        $user     = auth()->user();
        $payments = Transaction::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('payment_history', 'success', ['Historial de pagos'], [
            'payments' => $payments,
        ]);
    }

    public function gateways()
    {
        $gateways = Gateway::active()->with('singleCurrency')->get()
            ->map(fn($g) => [
                'id'          => $g->id,
                'code'        => $g->code,
                'name'        => $g->name,
                'type'        => $g->code < 1000 ? 'automatic' : 'manual',
                'image'       => $g->singleCurrency?->image,
                'currency'    => $g->singleCurrency?->currency,
                'symbol'      => $g->singleCurrency?->symbol,
                'is_cash'     => false,
                'description' => $g->description ?? null,
            ]);

        $gateways->prepend([
            'id'       => 0,
            'code'     => 0,
            'name'     => 'Efectivo',
            'image'    => null,
            'currency' => gs('cur_text'),
            'symbol'   => gs('cur_sym'),
            'is_cash'  => true,
        ]);

        return apiResponse('gateways', 'success', ['Métodos de pago'], [
            'gateways'         => $gateways,
            'gateway_image_path' => 'assets/images/gateway',
        ]);
    }
}
