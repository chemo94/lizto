<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'amount'      => 'required|numeric|min:1',
            'gateway_code' => 'nullable|exists:gateways,code',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();
        $this->ensureWallet($user);

        // Manual addition for demo
        $user->wallet->credit($request->amount, 'deposit', 'Recarga de billetera S/ ' . $request->amount);

        return apiResponse('funds_added', 'success', ['Fondos agregados correctamente'], [
            'new_balance' => $user->wallet->balance,
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

    private function ensureWallet($holder)
    {
        if (!$holder->wallet) {
            $wallet = Wallet::create(['holder_type' => get_class($holder), 'holder_id' => $holder->id]);
            $holder->update(['wallet_id' => $wallet->id]);
        }
        return $holder->fresh()->wallet;
    }
}
