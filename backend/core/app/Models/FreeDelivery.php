<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreeDelivery extends Model
{
    protected $table = 'free_deliveries';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->status && $this->remaining > 0;
    }
}
