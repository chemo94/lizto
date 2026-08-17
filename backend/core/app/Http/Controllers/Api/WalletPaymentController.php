<?php

namespace App\Http\Controllers\Api;

use App\Events\NewDeliveryOrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Gateway;
use App\Models\WalletTransaction;
use App\Services\FcmService;
use Illuminate\Http\Request;

class WalletPaymentController extends Controller
{
    public function mercadoPagoIpn(Request $request)
    {
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            return response('Invalid Request', 400);
        }

        $gateway = Gateway::where('alias', 'MercadoPago')->first();
        if (!$gateway) {
            return response('Gateway not found', 400);
        }

        $param = json_decode($gateway->gateway_parameters);
        $accessToken = $param->access_token->value ?? ($param->access_token ?? '');

        if (!$accessToken) {
            return response('Invalid gateway config', 400);
        }

        $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $paymentId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $paymentData = curl_exec($ch);
        curl_close($ch);

        $payment = json_decode($paymentData, true);

        if (!isset($payment['status']) || $payment['status'] !== 'approved') {
            return response('Payment not approved', 400);
        }

        $reference = $payment['external_reference'] ?? null;
        if (!$reference && isset($payment['additional_info']['items'][0]['id'])) {
            $reference = $payment['additional_info']['items'][0]['id'];
        }

        if (!$reference) {
            return response('No transaction reference', 400);
        }

        // Try wallet transaction first
        $transaction = WalletTransaction::with('wallet')->where('trx', $reference)->where('status', 0)->first();
        if ($transaction) {
            $wallet = $transaction->wallet;
            $wallet->balance += $transaction->amount;
            $wallet->save();
            $transaction->update(['post_balance' => $wallet->balance, 'status' => 1]);
            return response('OK', 200);
        }

        // Try delivery order (by order_no)
        $order = DeliveryOrder::where('order_no', $reference)->where('status', 'pending_payment')->first();
        if ($order) {
            $order->update(['status' => 'pending']);

            try {
                broadcast(new NewDeliveryOrderPlaced($order, 'delivery'));
                if ($order->store?->seller) {
                    FcmService::sendToSeller(
                        $order->store->seller,
                        'Nuevo pedido #' . $order->order_no,
                        $order->store->name . ' - S/ ' . number_format($order->total, 2),
                        ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
                    );
                }
                // Send FCM to Admin when paid
                FcmService::sendToAdmin(
                    'Nuevo pedido #' . $order->order_no . ' (Pagado)',
                    ($order->store?->name ?? 'Delivery') . ' - S/ ' . number_format($order->total, 2),
                    ['order_id' => (string) $order->id, 'order_no' => $order->order_no, 'type' => 'new_order']
                );
            } catch (\Exception $e) {}

            return response('OK', 200);
        }

        return response('Reference not found', 404);
    }
}
