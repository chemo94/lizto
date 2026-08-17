<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavorMessage extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];

    public function favor()
    {
        return $this->belongsTo(Favor::class);
    }

    public function sender()
    {
        return $this->morphTo();
    }
}
