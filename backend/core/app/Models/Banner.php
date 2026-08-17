<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use GlobalStatus;

    protected $casts = [
        'status'     => 'integer',
        'sort_order' => 'integer',
    ];

    // Posibles valores: 'taxi' | 'delivery'
    public const TYPE_TAXI     = 'taxi';
    public const TYPE_DELIVERY = 'delivery';

    public function scopeForTaxi($query)
    {
        return $query->where('type', self::TYPE_TAXI);
    }

    public function scopeForDelivery($query)
    {
        return $query->where('type', self::TYPE_DELIVERY);
    }
}
