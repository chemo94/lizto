<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSchedule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'store_id' => 'integer',
        'day'      => 'integer',
    ];

    public static function days()
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function dayName(): string
    {
        return self::days()[$this->day] ?? 'Desconocido';
    }

    public function scopeToday($query)
    {
        return $query->where('day', now()->dayOfWeek);
    }
}
