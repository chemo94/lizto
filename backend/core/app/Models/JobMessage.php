<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobMessage extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];

    public function job()
    {
        return $this->morphTo();
    }

    public function sender()
    {
        return $this->morphTo();
    }
}
