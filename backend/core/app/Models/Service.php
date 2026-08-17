<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use  GlobalStatus;
    protected $casts = [
        'city_min_fare'             => 'double',
        'city_max_fare'             => 'double',
        'city_recommend_fare'       => 'double',
        'city_fare_commission'      => 'double',
        'city_base_fare'            => 'double',
        'city_rate_per_km'          => 'double',
        'city_min_trip_fare'        => 'double',
        'intercity_min_fare'        => 'double',
        'intercity_max_fare'        => 'double',
        'intercity_recommend_fare'  => 'double',
        'intercity_fare_commission' => 'double',
        'intercity_base_fare'       => 'double',
        'intercity_rate_per_km'     => 'double',
        'intercity_min_trip_fare'   => 'double',
        'status'                    => 'integer',
    ];
}
