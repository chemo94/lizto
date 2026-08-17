<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'driver_id',
        'seller_id',
        'admin_id',
        'pos_staff_id',
        'is_app',
        'app_type',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'driver_id'    => 'integer',
        'seller_id'    => 'integer',
        'admin_id'     => 'integer',
        'pos_staff_id' => 'integer',
        'is_app'       => 'integer',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
