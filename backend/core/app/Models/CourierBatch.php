<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_orders'           => 'integer',
        'total_distance_km'      => 'double',
        'total_duration_minutes' => 'double',
        'base_earning'           => 'double',
        'distance_earning'       => 'double',
        'time_earning'           => 'double',
        'batch_bonus'            => 'double',
        'demand_incentive'       => 'double',
        'driver_earning'         => 'double',
        'total_tips'             => 'double',
        'total_payout'           => 'double',
        'total_points'           => 'integer',
        'demand_multiplier'      => 'double',
        'optimized_stops'        => 'array',
        'fare_breakdown'         => 'array',
        'expires_at'             => 'datetime',
        'accepted_at'            => 'datetime',
        'started_at'             => 'datetime',
        'completed_at'           => 'datetime',
    ];

    public const TYPE_SINGLE    = 'SINGLE';
    public const TYPE_DOUBLE    = 'DOUBLE';
    public const TYPE_TRIPLET   = 'TRIPLET';
    public const TYPE_QUADRUPLE = 'QUADRUPLE';

    public const STATUS_PENDING     = 'pending';
    public const STATUS_OFFERED     = 'offered';
    public const STATUS_ACCEPTED    = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function batchOrders()
    {
        return $this->hasMany(CourierBatchOrder::class, 'batch_id')->orderBy('sequence_order');
    }

    public function offers()
    {
        return $this->hasMany(CourierJobOffer::class, 'batch_id');
    }

    public static function generateBatchNo(): string
    {
        return 'BAT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public static function determineType(int $orderCount): string
    {
        return match ($orderCount) {
            1 => self::TYPE_SINGLE,
            2 => self::TYPE_DOUBLE,
            3 => self::TYPE_TRIPLET,
            default => self::TYPE_QUADRUPLE,
        };
    }
}
