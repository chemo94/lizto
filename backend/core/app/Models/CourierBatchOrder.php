<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierBatchOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sequence_order'     => 'integer',
        'pickup_stop_no'     => 'integer',
        'dropoff_stop_no'    => 'integer',
        'individual_earning' => 'double',
        'tip'                => 'double',
        'points'             => 'integer',
        'fare_breakdown'     => 'array',
        'picked_up_at'       => 'datetime',
        'delivered_at'       => 'datetime',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public function batch()
    {
        return $this->belongsTo(CourierBatch::class, 'batch_id');
    }

    public function order()
    {
        return $this->morphTo('order', 'order_type', 'order_id');
    }
}
