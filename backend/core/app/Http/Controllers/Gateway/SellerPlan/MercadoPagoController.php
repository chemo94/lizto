<?php

namespace App\Http\Controllers\Gateway\SellerPlan;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\StorePackage;
use App\Models\StorePackagePayment;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    public function ipn(Request $request)
    {
        $paymentId = $request->data['id'] ?? $request->id ?? null;
        if (!$paymentId) {
            return response('Invalid Request', 400);
        }

        $gateway = Gateway::where('alias', 'MercadoPago')->first();
        $params = json_decode($gateway?->gateway_parameters);
        $accessToken = $params->access_token->value ?? ($params->access_token ?? null);

        if (!$accessToken) {
            return response('Gateway unavailable', 400);
        }

        $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $paymentId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $payload = json_decode($response, true);
        $trx = $payload['external_reference']
            ?? ($payload['additional_info']['items'][0]['id'] ?? null);

        $payment = $trx ? StorePackagePayment::where('trx', $trx)->first() : null;
        if (!$payment) {
            return response('Payment not found', 404);
        }

        $payment->update([
            'payment_id' => (string) $paymentId,
            'payload'    => ['payment' => $payload],
        ]);

        if (($payload['status'] ?? null) !== 'approved') {
            return response('Payment not approved', 400);
        }

        if ($payment->status === 'paid') {
            return response('OK', 200);
        }

        $package = $payment->package;
        $existingActive = StorePackage::where('store_id', $payment->store_id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        $startsAt = now();
        if ($existingActive && $existingActive->package_id === $payment->package_id) {
            $startsAt = $existingActive->expires_at;
        }

        $expiresAt = $package->duration_days > 0 ? \Carbon\Carbon::parse($startsAt)->addDays($package->duration_days) : null;

        if ($existingActive && $existingActive->package_id !== $payment->package_id) {
            $startsAt = now();
            $expiresAt = $package->duration_days > 0 ? now()->addDays($package->duration_days) : null;

            StorePackage::where('store_id', $payment->store_id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
        }

        $subscription = StorePackage::create([
            'store_id'       => $payment->store_id,
            'seller_id'      => $payment->seller_id,
            'package_id'     => $payment->package_id,
            'status'         => 'active',
            'amount_paid'    => $payment->package_amount,
            'payment_method' => 'MercadoPago',
            'payment_ref'    => (string) $paymentId,
            'starts_at'      => $startsAt,
            'expires_at'     => $expiresAt,
            'notes'          => 'Pago ' . $payment->trx . ' incluye comisión de pasarela S/ ' . number_format($payment->gateway_fee, 2),
        ]);

        $payment->update([
            'store_package_id' => $subscription->id,
            'status'           => 'paid',
            'paid_at'          => now(),
        ]);

        return response('OK', 200);
    }
}
