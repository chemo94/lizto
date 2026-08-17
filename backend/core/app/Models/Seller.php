<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Seller extends Authenticatable
{
    use HasApiTokens, GlobalStatus;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['status' => 'integer', 'is_verified' => 'integer', 'receivable_balance' => 'double'];

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'holder');
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function receivableTransactions()
    {
        return $this->hasMany(SellerReceivableTransaction::class)->orderBy('id', 'desc');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'seller_id');
    }
}
