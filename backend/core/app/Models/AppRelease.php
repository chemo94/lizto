<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'latest_build' => 'integer',
        'minimum_build' => 'integer',
        'force_update' => 'boolean',
        'is_active' => 'boolean',
    ];
}
