<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderItemVariation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['variation_price' => 'double'];

    public function orderItem()
    {
        return $this->belongsTo(DeliveryOrderItem::class, 'delivery_order_item_id');
    }
}
