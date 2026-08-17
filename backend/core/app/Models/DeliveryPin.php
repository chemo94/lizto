<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPin extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['verified_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(DeliveryOrder::class, 'order_id');
    }

    public function favor()
    {
        return $this->belongsTo(Favor::class, 'favor_id');
    }
}
