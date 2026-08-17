<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_price'  => 'double',
        'total_price' => 'double',
    ];

    public function order()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function addons()
    {
        return $this->hasMany(DeliveryOrderItemAddon::class, 'delivery_order_item_id');
    }

    public function variation()
    {
        return $this->hasOne(DeliveryOrderItemVariation::class, 'delivery_order_item_id');
    }
}
