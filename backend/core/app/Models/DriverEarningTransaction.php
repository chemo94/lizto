<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverEarningTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount'       => 'double',
        'post_balance' => 'double',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function ref()
    {
        return $this->morphTo();
    }
}
