<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierProof extends Model
{
    protected $guarded = ['id'];

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }
}
