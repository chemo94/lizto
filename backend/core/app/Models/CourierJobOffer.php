<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierJobOffer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'notification_delivered' => 'boolean',
        'offered_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function job()
    {
        return $this->morphTo();
    }
}
