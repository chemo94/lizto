<?php

namespace App\Http\Controllers\Api\Seller;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    private function seller()
    {
        $seller = auth()->user();
        if (!$seller) {
            abort(response()->json(['remark' => 'error', 'message' => ['No autenticado']], 401));
        }
        return $seller;
    }

    public function getBalance()
    {
        $seller = $this->seller();
        $this->ensureWallet($seller);

        return apiResponse('balance', 'success', ['Saldo de billetera'], [
            'balance'           => $seller->wallet->balance,
            'blocked_balance'   => $seller->wallet->blocked_balance,
            'available_balance' => $seller->wallet->balance - $seller->wallet->blocked_balance,
        ]);
    }

    public function getTransactions(Request $request)
    {
        $seller = $this->seller();
        $this->ensureWallet($seller);

        $transactions = \App\Models\WalletTransaction::where('wallet_id', $seller->wallet->id)
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('transactions', 'success', ['Historial de transacciones'], [
            'transactions' => $transactions,
        ]);
    }

    public function withdrawRequest(Request $request)
    {
        $seller = $this->seller();

        $validator = Validator::make($request->all(), [
            'amount'       => 'required|numeric|min:1',
            'bank_details' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $this->ensureWallet($seller);

        if ($seller->wallet->balance < $request->amount) {
            return apiResponse('insufficient_funds', 'error', ['Saldo insuficiente para el retiro']);
        }

        $withdrawal                     = new Withdrawal();
        $withdrawal->user_type          = 'App\Models\Seller';
        $withdrawal->user_id            = $seller->id;
        $withdrawal->amount             = $request->amount;
        $withdrawal->charge             = 0;
        $withdrawal->final_amount       = $request->amount;
        $withdrawal->after_charge       = $request->amount;
        $withdrawal->rate               = 1;
        $withdrawal->currency           = gs('cur_text') ?? 'PEN';
        $withdrawal->trx                = getTrx();
        $withdrawal->status             = Status::PAYMENT_PENDING;
        $withdrawal->withdraw_information = ['bank_details' => $request->bank_details];
        $withdrawal->save();

        $seller->wallet->debit($request->amount, 'withdraw', 'Retiro de S/ ' . number_format($request->amount, 2));

        return apiResponse('withdraw_requested', 'success', ['Solicitud de retiro enviada'], [
            'trx' => $withdrawal->trx,
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
