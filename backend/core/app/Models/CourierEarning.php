<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierEarning extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'amount'    => 'double',
        'commission' => 'double',
    ];

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }

    public function job()
    {
        return $this->morphTo();
    }
}
