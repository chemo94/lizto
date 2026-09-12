<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierEarning extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'amount'    => 'double',
        'commission' => 'double',
        'commission_base_percent' => 'double',
        'commission_effective_percent' => 'double',
        'commission_minimum' => 'double',
        'completed_jobs_snapshot' => 'integer',
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
