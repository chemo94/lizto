<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getBalance()
    {
        $user = auth()->user();
        $this->ensureWallet($user);

        return apiResponse('balance', 'success', ['Saldo de billetera'], [
            'balance'          => $user->wallet->balance,
            'blocked_balance'  => $user->wallet->blocked_balance,
            'available_balance' => $user->wallet->balance - $user->wallet->blocked_balance,
        ]);
    }

    public function getTransactions(Request $request)
    {
        $user = auth()->user();
        $this->ensureWallet($user);

        $transactions = $user->wallet->transactions()
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('transactions', 'success', ['Historial de transacciones'], [
            'transactions' => $transactions,
        ]);
    }

    public function addFunds(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'amount'        => 'required|numeric|min:1',
            'gateway_alias' => 'nullable|string|in:mercadopago',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();
        $this->ensureWallet($user);

        $amount  = (float) $request->amount;
        $trx     = getTrx();
        $gatewayAlias = $request->gateway_alias ?? null;

        // If MercadoPago, generate payment URL FIRST before creating any transaction
        if ($gatewayAlias === 'mercadopago') {
            $gateway = Gateway::where('alias', 'MercadoPago')->active()->first();

            if (!$gateway) {
                return apiResponse('gateway_error', 'error', ['MercadoPago no está configurado']);
            }

            $param = json_decode($gateway->gateway_parameters);
            $accessToken = $param->access_token->value ?? ($param->access_token ?? '');

            if (!$accessToken) {
                return apiResponse('gateway_error', 'error', ['Token de acceso de MercadoPago no configurado']);
            }

            $paymentUrl = $this->createMercadoPagoPreference($accessToken, $trx, $amount, $user, $gateway);

            if (!$paymentUrl) {
                return apiResponse('payment_error', 'error', ['No se pudo iniciar el pago con MercadoPago. Intenta de nuevo.']);
            }

            // Only create transaction if payment URL was generated successfully
            $user->wallet->transactions()->create([
                'trx'          => $trx,
                'amount'       => $amount,
                'post_balance' => $user->wallet->balance,
                'charge'       => 0,
                'trx_type'     => '+',
                'details'      => 'Recarga de S/ ' . number_format($amount, 2) . ' vía MercadoPago',
                'remark'       => 'deposit',
                'status'       => 0,
            ]);

            return apiResponse('redirect_to_payment', 'success', ['Redirigir a MercadoPago'], [
                'redirect_url' => $paymentUrl,
                'trx'          => $trx,
                'amount'       => $amount,
                'status'       => 'pending',
            ]);
        }

        // No gateway — credit directly
        $user->wallet->credit($amount, 'deposit', 'Recarga de S/ ' . number_format($amount, 2));

        return apiResponse('funds_added', 'success', ['Fondos agregados correctamente'], [
            'new_balance' => $user->wallet->balance,
            'trx'         => $trx,
        ]);
    }

    public function verifyPayment(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'trx' => 'required|string|exists:wallet_transactions,trx',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();
        $trx  = WalletTransaction::where('trx', $request->trx)
            ->whereHas('wallet', fn($q) => $q->where('holder_type', get_class($user))->where('holder_id', $user->id))
            ->firstOrFail();

        if ($trx->status == 1) {
            $this->ensureWallet($user);
            return apiResponse('payment_verified', 'success', ['Pago ya confirmado'], [
                'status'    => 'completed',
                'balance'   => $user->wallet->balance,
            ]);
        }

        return apiResponse('payment_pending', 'success', ['Pago pendiente'], [
            'status' => 'pending',
            'trx'    => $trx->trx,
        ]);
    }

    public function withdrawFunds(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'method_code'  => 'required|exists:withdraw_methods,id',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();
        $this->ensureWallet($user);

        if ($user->wallet->balance < $request->amount) {
            return apiResponse('insufficient_funds', 'error', ['Saldo insuficiente']);
        }

        $result = $user->wallet->debit($request->amount, 'withdraw', 'Retiro de fondos S/ ' . $request->amount);

        if (!$result) {
            return apiResponse('error', 'error', ['No se pudo procesar el retiro']);
        }

        return apiResponse('withdraw_processed', 'success', ['Retiro procesado'], [
            'new_balance' => $user->wallet->balance,
        ]);
    }

    private function createMercadoPagoPreference($accessToken, $trx, $amount, $user, $gateway)
    {
        try {
            $preferenceData = [
                'items' => [[
                    'id'          => $trx,
                    'title'       => 'Recarga de billetera',
                    'description' => 'Recarga S/ ' . $amount,
                    'quantity'    => 1,
                    'currency_id' => 'PEN',
                    'unit_price'  => $amount,
                ]],
                'payer' => ['email' => $user->email],
                'back_urls' => [
                    'success' => url('/'),
                    'pending' => url('/'),
                    'failure' => url('/'),
                ],
                'notification_url' => url('/api/ipn/wallet-mercadopago'),
                'auto_return' => 'approved',
                'external_reference' => $trx,
            ];

            $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($preferenceData),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);

            return $result['init_point'] ?? $result['sandbox_init_point'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function ensureWallet($holder)
    {
        if (!$holder->wallet) {
            $wallet = Wallet::create(['holder_type' => get_class($holder), 'holder_id' => $holder->id]);
            $holder->update(['wallet_id' => $wallet->id]);
        }
        return $holder->fresh()->wallet;
    }
}
