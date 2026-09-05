<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Events\NewDeliveryOrderPlaced;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DeliveryOrderItemAddon;
use App\Models\DeliveryOrderItemVariation;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FcmService;
use App\Support\DeliveryPricing;
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
            'payment_method_code' => 'nullable',
            'cash_pay_amount'   => 'nullable|numeric|min:0',
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
                    if ((float) $variation->price > 0) {
                        $unitPrice = (float) $variation->price;
                    }
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

        $estimate = DeliveryPricing::estimateForStore($store, $deliveryLat, $deliveryLng);
        if (!$estimate['in_coverage']) {
            return apiResponse('outside_delivery_coverage', 'error', ['La dirección está fuera de la zona de cobertura']);
        }

        $deliveryFee = $estimate['delivery_fee'];
        $tip         = $request->tip ?? 0;
        $discount    = 0;

        // ── Cupón ──
        $coupon = null;
        if ($request->coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $request->coupon_code)->first();
            if (!$coupon || !$coupon->isValid($user->id)) {
                return apiResponse('invalid_coupon', 'error', ['Cupón inválido o ya utilizado']);
            }
            if ($subtotal < $coupon->min_order) {
                return apiResponse('invalid_coupon', 'error', ['Pedido mínimo para este cupón: S/ ' . number_format($coupon->min_order, 2)]);
            }
            if ($coupon->type === 'free_delivery') {
                $discount = $deliveryFee;
            } else {
                $discount = $coupon->calcDiscount($subtotal);
            }
        }

        // ── Envío gratis asignado ──
        if (!$coupon || $coupon->type !== 'free_delivery') {
            $freeDelivery = \App\Models\FreeDelivery::where('user_id', $user->id)
                ->where('status', 1)->where('remaining', '>', 0)->first();
            if ($freeDelivery) {
                $discount = max($discount, $deliveryFee);
                $freeDelivery->decrement('remaining');
            }
        }

        $total = $subtotal + $deliveryFee + $tip - $discount;

        $paymentMethodCode = null;
        $paymentMethodName = null;
        $selectedGateway   = null;
        if ($request->has('payment_method_code') && $request->payment_method_code !== null && $request->payment_method_code !== '') {
            if ((string) $request->payment_method_code === '0') {
                $paymentMethodCode = 0;
                $paymentMethodName = 'Efectivo';
            } else {
                $selectedGateway = Gateway::where('code', $request->payment_method_code)->active()->first();
                if (!$selectedGateway) {
                    return apiResponse('invalid_gateway', 'error', ['Método de pago inválido']);
                }
                $paymentMethodCode = $selectedGateway->code;
                $paymentMethodName = $selectedGateway->name;
            }
        }

        $orderNo = 'DEL-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(6));

        $order = DeliveryOrder::create([
            'order_no'           => $orderNo,
            'user_id'            => $user->id,
            'store_id'           => $store->id,
            'subtotal'           => $subtotal,
            'delivery_fee'       => $deliveryFee,
            'discount'           => $discount,
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
            'payment_method_name' => $paymentMethodName,
            'cash_pay_amount'    => $request->cash_pay_amount,
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

        $order->load('items.variation', 'items.addons', 'store');

        // If payment is MercadoPago, generate preference — no broadcast/FCM until payment confirmed
        $isMercadoPago = $selectedGateway && (
            $selectedGateway->code == 119 ||
            in_array((int) $selectedGateway->code, [119, 9002]) ||
            stripos($selectedGateway->name ?? '', 'mercadopago') !== false
        );

        if ($isMercadoPago) {
            $mpData = $this->getMercadoPagoCheckoutApiData($selectedGateway, $order, $user);

            if ($mpData) {
                $order->update(['status' => 'pending_payment']);
                return apiResponse('payment_required', 'success', ['Checkout API MercadoPago'], array_merge([
                    'order_id'   => $order->id,
                    'order'      => $order,
                    'status'     => 'pending_payment',
                ], $mpData));
            }

            return apiResponse('payment_error', 'error', ['No se pudo iniciar el pago. Intenta de nuevo.']);
        }

        // Not MercadoPago — notify seller/admin immediately
        broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));

        if ($order->store && $order->store->seller) {
            FcmService::sendToSeller($order->store->seller, 'Nuevo pedido #' . $order->order_no, $order->store->name . ' - ' . $order->items->count() . ' productos por S/ ' . number_format($order->total, 2), ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']);
        }

        FcmService::sendToAdmin('Nuevo pedido #' . $order->order_no, ($order->store->name ?? 'Delivery') . ' - S/ ' . number_format($order->total, 2), ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']);

        // Registrar uso de cupón
        if ($coupon) {
            \App\Models\CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'order_id' => $order->id]);
            $coupon->increment('usage_count');
        }

        return apiResponse('order_created', 'success', ['Pedido creado correctamente'], [
            'order'              => $order,
            'delivery_estimate'  => $estimate,
            'product_image_path' => 'storage',
            'store_image_path'   => 'assets/images/store',
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
            'store_image_path'   => 'assets/images/store',
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
            'store_image_path'   => 'assets/images/store',
            'driver_image_path'  => 'assets/images/driver',
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
            ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
            ->with('store.seller')
            ->findOrFail($id);

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason ?? 'Cancelado por el usuario',
        ]);

        $seller = $order->store->seller ?? null;
        if ($seller) {
            FcmService::sendToSeller($seller,
                'Pedido cancelado',
                'El pedido #' . $order->order_no . ' ha sido cancelado por el cliente',
                ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'order_cancelled']
            );
        }

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
                'id'             => $g->id,
                'code'           => $g->code,
                'name'           => $g->name,
                'type'           => $g->code < 1000 ? 'automatic' : 'manual',
                'image'          => $g->singleCurrency?->image,
                'currency'       => $g->singleCurrency?->currency,
                'symbol'         => $g->singleCurrency?->symbol,
                'is_cash'        => false,
                'description'    => $g->description ?? null,
                'percent_charge' => (float) ($g->singleCurrency?->percent_charge ?? 0),
                'fixed_charge'   => (float) ($g->singleCurrency?->fixed_charge ?? 0),
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

    public function pay(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->with('items.variation', 'items.addons', 'store')
            ->findOrFail($id);

        $selectedGateway = null;
        if ($request->has('payment_method_code') && $request->payment_method_code !== null && $request->payment_method_code !== '') {
            $selectedGateway = Gateway::where('code', $request->payment_method_code)->active()->first();
        }

        $paymentMethodCode = $selectedGateway->code ?? $order->payment_method_code;
        $paymentMethodName = $selectedGateway->name ?? $order->payment_method_name;

        $order->update([
            'payment_method_code' => $paymentMethodCode,
            'payment_method_name' => $paymentMethodName,
        ]);

        $isMercadoPago = $selectedGateway && (
            $selectedGateway->code == 119 ||
            in_array((int) $selectedGateway->code, [119, 9002]) ||
            stripos($selectedGateway->name ?? '', 'mercadopago') !== false
        );

        if ($isMercadoPago) {
            $mpData = $this->getMercadoPagoCheckoutApiData($selectedGateway, $order, $user);

            if ($mpData) {
                return apiResponse('payment_required', 'success', ['Checkout API MercadoPago'], array_merge([
                    'order_id' => $order->id,
                ], $mpData));
            }

            return apiResponse('payment_error', 'error', ['No se pudo iniciar el pago con MercadoPago']);
        }

        // Non-MercadoPago (Yape, Plin, Efectivo, transferencia, etc.)
        $order->update(['status' => 'pending']);
        $order->refresh()->load('items.variation', 'items.addons', 'store');

        broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));

        if ($order->store && $order->store->seller) {
            FcmService::sendToSeller(
                $order->store->seller,
                'Nuevo pedido #' . $order->order_no,
                $order->store->name . ' - ' . $order->items->count() . ' productos por S/ ' . number_format($order->total, 2),
                ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
            );
        }

        FcmService::sendToAdmin('Nuevo pedido #' . $order->order_no, ($order->store->name ?? 'Delivery') . ' - S/ ' . number_format($order->total, 2), ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']);

        return apiResponse('payment_method_updated', 'success', ['Método de pago actualizado'], [
            'order_id' => $order->id,
            'status'   => 'pending',
        ]);
    }

    /**
     * Returns the data needed for Checkout API (no redirect): public_key, amount, currency, order_no.
     */
    private function getMercadoPagoCheckoutApiData($gateway, $order, $user): ?array
    {
        $param       = json_decode($gateway->gateway_parameters);
        $accessToken = $param->access_token->value ?? ($param->access_token ?? '');
        $publicKey   = $param->public_key->value   ?? ($param->public_key   ?? '');

        if (!$accessToken || !$publicKey) return null;

        $currency     = $gateway->singleCurrency;
        $pct          = max(0, (float) ($currency->percent_charge ?? 0));
        $fix          = max(0, (float) ($currency->fixed_charge  ?? 0));
        $baseTotal    = (float) $order->total;
        $totalWithFee = $pct >= 100
            ? $baseTotal + $fix
            : round(($baseTotal + $fix) / (1 - ($pct / 100)), 2);

        return [
            'checkout_api'       => true,
            'public_key'         => $publicKey,
            'order_no'           => $order->order_no,
            'amount'             => $totalWithFee,
            'base_amount'        => $baseTotal,
            'gateway_fee'        => round($totalWithFee - $baseTotal, 2),
            'currency'           => $currency->currency ?? 'PEN',
            'payer_email'        => $user->email ?? '',
            'description'        => 'Pedido ' . $order->order_no . ' - ' . ($order->store->name ?? ''),
            'notification_url'   => url('/api/ipn/wallet-mercadopago'),
            'gateway_percent'    => $pct,
            'gateway_fixed'      => $fix,
        ];
    }

    /**
     * Process a MercadoPago Checkout API payment (card token from client).
     * POST /api/delivery/orders/{id}/mp-process
     */
    public function mpCheckoutProcess(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->with('store')
            ->findOrFail($id);

        $request->validate([
            'card_token'       => 'required|string',
            'installments'     => 'required|integer|min:1',
            'payment_method_id'=> 'required|string',
            'issuer_id'        => 'nullable',
            'payer_email'      => 'required|email',
            'payer_doc_type'   => 'nullable|string',
            'payer_doc_num'    => 'nullable|string',
        ]);

        $gateway     = Gateway::where('code', $order->payment_method_code)->active()->first()
                    ?? Gateway::where('alias', 'MercadoPago')->active()->first();

        if (!$gateway) {
            return apiResponse('gateway_error', 'error', ['Gateway MercadoPago no encontrado']);
        }

        $param       = json_decode($gateway->gateway_parameters);
        $accessToken = $param->access_token->value ?? ($param->access_token ?? '');

        if (!$accessToken) {
            return apiResponse('gateway_error', 'error', ['Access Token de MercadoPago no configurado']);
        }

        $currency     = $gateway->singleCurrency;
        $pct          = max(0, (float) ($currency->percent_charge ?? 0));
        $fix          = max(0, (float) ($currency->fixed_charge  ?? 0));
        $baseTotal    = (float) $order->total;
        $totalWithFee = $pct >= 100
            ? $baseTotal + $fix
            : round(($baseTotal + $fix) / (1 - ($pct / 100)), 2);

        $paymentData = [
            'transaction_amount' => $totalWithFee,
            'token'              => $request->card_token,
            'description'        => 'Pedido ' . $order->order_no,
            'installments'       => (int) $request->installments,
            'payment_method_id'  => $request->payment_method_id,
            'issuer_id'          => $request->issuer_id ?: null,
            'payer'              => [
                'email'          => $request->payer_email,
                'identification' => [
                    'type'   => $request->payer_doc_type ?: 'DNI',
                    'number' => $request->payer_doc_num  ?: '',
                ],
            ],
            'external_reference'  => $order->order_no,
            'notification_url'    => url('/api/ipn/wallet-mercadopago'),
            'metadata'            => ['order_no' => $order->order_no, 'order_id' => $order->id],
        ];

        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($paymentData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'X-Idempotency-Key: delivery-' . $order->order_no,
            ],
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return apiResponse('connection_error', 'error', ['Error de conexión con MercadoPago. Intenta de nuevo.']);
        }

        $result   = json_decode($response, true);
        $mpStatus = $result['status'] ?? null;

        // Store payment id for tracking
        $order->update(['mp_payment_id' => $result['id'] ?? null]);

        if ($mpStatus === 'approved') {
            $order->update(['status' => 'pending']);
            $order->refresh()->load('items.variation', 'items.addons', 'store');

            broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));

            if ($order->store && $order->store->seller) {
                FcmService::sendToSeller(
                    $order->store->seller,
                    'Nuevo pedido #' . $order->order_no,
                    $order->store->name . ' - S/ ' . number_format($order->total, 2),
                    ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
                );
            }

            FcmService::sendToAdmin(
                'Nuevo pedido #' . $order->order_no . ' (Pagado)',
                ($order->store->name ?? 'Delivery') . ' - S/ ' . number_format($order->total, 2),
                ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
            );

            return apiResponse('payment_approved', 'success', ['¡Pago aprobado! Tu pedido está siendo preparado.'], [
                'mp_status'   => 'approved',
                'order_id'    => $order->id,
                'order_no'    => $order->order_no,
                'mp_payment_id' => $result['id'] ?? null,
            ]);
        }

        if ($mpStatus === 'in_process' || $mpStatus === 'pending') {
            return apiResponse('payment_pending', 'success', ['Tu pago está en revisión. Te notificaremos cuando se confirme.'], [
                'mp_status'     => 'pending',
                'order_id'      => $order->id,
                'mp_payment_id' => $result['id'] ?? null,
            ]);
        }

        // Rejected
        $detail = $result['status_detail'] ?? ($result['message'] ?? 'Pago rechazado');
        $friendlyMessages = [
            'cc_rejected_insufficient_amount'  => 'Fondos insuficientes en la tarjeta.',
            'cc_rejected_bad_filled_cvv'       => 'CVV incorrecto. Verifica el código de seguridad.',
            'cc_rejected_bad_filled_date'      => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_card_number' => 'Número de tarjeta incorrecto.',
            'cc_rejected_high_risk'            => 'Pago rechazado por seguridad. Contacta a tu banco.',
            'cc_rejected_call_for_authorize'   => 'Debes autorizar el pago con tu banco.',
            'cc_rejected_card_disabled'        => 'La tarjeta está desactivada.',
            'cc_rejected_duplicated_payment'   => 'Este pago fue procesado anteriormente.',
        ];
        $message = $friendlyMessages[$detail] ?? 'Pago rechazado: ' . str_replace('_', ' ', $detail);

        return apiResponse('payment_rejected', 'error', [$message], [
            'mp_status'        => $mpStatus,
            'mp_status_detail' => $detail,
            'order_id'         => $order->id,
        ], 422);
    }

    public function deletePendingOrder($id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->findOrFail($id);

        // Restore free delivery if it was used
        if ($order->discount > 0 && $order->discount == $order->delivery_fee) {
            $freeDelivery = \App\Models\FreeDelivery::where('user_id', $user->id)->first();
            if ($freeDelivery) {
                $freeDelivery->increment('remaining');
            }
        }

        $order->items()->delete();
        $order->delete();

        return apiResponse('order_deleted', 'success', ['Pedido cancelado y eliminado correctamente.']);
    }
}
