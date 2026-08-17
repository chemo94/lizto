<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class FleetOwner extends Authenticatable
{
    use HasApiTokens, GlobalStatus;

    protected $table = 'fleet_owners';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['status' => 'integer'];

    public function fleet()
    {
        return $this->hasOne(Fleet::class, 'owner_id');
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'holder');
    }
}
