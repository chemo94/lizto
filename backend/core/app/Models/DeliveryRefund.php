<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryRefund extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'double'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(DeliveryOrder::class, 'order_id');
    }

    public function favor()
    {
        return $this->belongsTo(Favor::class, 'favor_id');
    }
}
