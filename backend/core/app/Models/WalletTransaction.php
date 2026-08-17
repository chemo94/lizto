<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'double', 'post_balance' => 'double', 'charge' => 'double'];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ref()
    {
        return $this->morphTo();
    }
}
