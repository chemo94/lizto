<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTableReservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'reservation_time' => 'datetime',
        'dishes' => 'array',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function table()
    {
        return $this->belongsTo(PosTable::class, 'pos_table_id');
    }
}
