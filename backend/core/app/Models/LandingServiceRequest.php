<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingServiceRequest extends Model
{
    protected $fillable = [
        'service_type',
        'name',
        'phone',
        'pickup',
        'destination',
        'notes',
        'status',
        'ip_address',
        'store_id',
        'delivery_lat',
        'delivery_lng',
        'cart_json',
    ];

    protected $casts = [
        'cart_json'    => 'array',
        'delivery_lat' => 'double',
        'delivery_lng' => 'double',
    ];
}
