<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'balance'             => 'double',
        'blocked_balance'     => 'double',
        'promotional_balance' => 'double',
        'recharge_balance'    => 'double',
    ];

    public function holder()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('id', 'desc');
    }

    public function credit($amount, $remark = 'deposit', $details = null, $ref = null)
    {
        return $this->createTransaction($amount, '+', $remark, $details, $ref);
    }

    public function debit($amount, $remark = 'payment', $details = null, $ref = null)
    {
        // Las comisiones de servicios ya realizados siempre deben quedar
        // registradas contra la recarga, incluso si el saldo pasa a negativo.
        if ($remark === 'commission') {
            return $this->createTransaction($amount, '-', $remark, $details, $ref);
        }

        $allowNegative = false;
        $negativeLimit = 0;

        if ($this->holder_type === 'App\Models\Driver') {
            $negativeLimit = (float) (gs('negative_balance_driver') ?? 0);
            if ($negativeLimit < 0) {
                $allowNegative = true;
            }
        }

        if ($allowNegative) {
            $newBalance = $this->balance - $amount;
            if ($newBalance < $negativeLimit) return false;
        } else {
            if ($this->balance < $amount) return false;
        }

        return $this->createTransaction($amount, '-', $remark, $details, $ref);
    }

    private function createTransaction($amount, $type, $remark, $details, $ref)
    {
        $postBalance = $type === '+' ? $this->balance + $amount : $this->balance - $amount;

        $trx = $this->transactions()->create([
            'trx'          => getTrx(),
            'amount'       => $amount,
            'post_balance' => $postBalance,
            'charge'       => 0,
            'trx_type'     => $type,
            'details'      => $details ?? ucfirst($remark),
            'remark'       => substr($remark, 0, 50),
            'ref_type'     => $ref ? get_class($ref) : null,
            'ref_id'       => $ref ? $ref->id : null,
        ]);

        $this->update(['balance' => $postBalance]);
        return $trx;
    }
}
