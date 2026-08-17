<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'unit_price'         => 'double',
        'total_price'        => 'double',
        'substitute_price'   => 'double',
        'store_confirmed_at' => 'datetime',
        'customer_approved_at' => 'datetime',
        'customer_approved'  => 'boolean',
        'quantity'           => 'integer',
        'sort_order'         => 'integer',
    ];

    public function favor()
    {
        return $this->belongsTo(Favor::class);
    }

    public function getTotalPriceAttribute()
    {
        if ($this->attributes['total_price']) {
            return $this->attributes['total_price'];
        }
        if ($this->unit_price && $this->quantity) {
            return $this->unit_price * $this->quantity;
        }
        return null;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending'     => 'Pendiente',
            'found'       => 'Encontrado',
            'not_found'   => 'No encontrado',
            'substituted' => 'Sustituido',
            'cancelled'   => 'Cancelado',
            default       => $this->status,
        };
    }

    public function isConfirmable(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function isSubstitutable(): bool
    {
        return $this->status === 'not_found' && !$this->customer_approved_at;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFound($query)
    {
        return $query->where('status', 'found');
    }

    public function scopeNeedsApproval($query)
    {
        return $query->where('status', 'not_found')
                     ->whereNotNull('substitute_name')
                     ->whereNull('customer_approved_at');
    }
}
