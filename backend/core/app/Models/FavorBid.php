<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavorBid extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['bid_amount' => 'double'];

    public function favor()
    {
        return $this->belongsTo(Favor::class);
    }

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }
}
