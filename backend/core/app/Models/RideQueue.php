<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RideQueue extends Model
{
    protected $guarded = ['id'];

    protected $table = 'ride_queues';

    protected $casts = [
        'ride_id'        => 'integer',
        'ordering'       => 'integer',
        'dispatch_count' => 'integer',
    ];
}
