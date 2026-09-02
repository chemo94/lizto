<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosCustomerProfile extends Model
{
    protected $guarded = ['id'];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
}
