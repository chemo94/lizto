<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCommission extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'delivery_percent'       => 'double',
        'favor_percent'          => 'double',
        'min_commission'         => 'double',
        'courier_fixed_amount'   => 'double',
        'store_fixed_amount'     => 'double',
        'store_commission_percent' => 'double',
    ];
}
