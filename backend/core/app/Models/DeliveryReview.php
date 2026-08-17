<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryReview extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['rating' => 'double'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }

    public function reviewable()
    {
        return $this->morphTo();
    }
}
