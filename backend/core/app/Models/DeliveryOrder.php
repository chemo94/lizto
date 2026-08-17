<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'subtotal'            => 'double',
        'delivery_fee'        => 'double',
        'discount'            => 'double',
        'tip'                 => 'double',
        'total'               => 'double',
        'delivery_lat'        => 'double',
        'delivery_lng'        => 'double',
        'driver_assigned_at'  => 'datetime',
        'delivered_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
        'payment_method_code' => 'double',
        'payment_status'      => 'integer',
        'cash_pay_amount'     => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeBySeller($q, $sellerId)
    {
        return $q->whereHas('store', fn($q) => $q->where('seller_id', $sellerId));
    }
}
