<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingConfirmation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'double',
    ];

    public function favor()
    {
        return $this->belongsTo(Favor::class);
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'receipt'          => 'Factura/Ticket',
            'product_photo'    => 'Foto de productos',
            'substitution'     => 'Sustituto',
            'delivery_proof'   => 'Evidencia de entrega',
            default            => $this->type,
        };
    }
}
