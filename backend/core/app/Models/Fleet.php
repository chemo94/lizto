<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];
    protected $casts = [
        'zone_id' => 'integer',
        'lizto_commission_rate' => 'double',
        'driver_commission_rate' => 'double',
        'base_fare' => 'double',
        'rate_per_km' => 'double',
        'rate_per_minute' => 'double',
        'status' => 'integer'
    ];

    public function owner()
    {
        return $this->belongsTo(FleetOwner::class, 'owner_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'fleet_id');
    }

    public function rides()
    {
        return $this->hasMany(Ride::class, 'fleet_id');
    }
}
