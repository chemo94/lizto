<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_minor'       => 'boolean',
        'claim_type'     => 'integer',
        'status'         => 'integer',
        'amount_claimed' => 'double',
        'responded_at'   => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING     = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_RESOLVED    = 2;
    const STATUS_REJECTED    = 3;

    // Type Constants
    const TYPE_CLAIM     = 1; // Reclamo
    const TYPE_COMPLAINT = 2; // Queja

    public function getStatusBadgeAttribute()
    {
        $html = '';
        if ($this->status == self::STATUS_PENDING) {
            $html = '<span class="badge badge--warning">' . __('Pendiente') . '</span>';
        } elseif ($this->status == self::STATUS_IN_PROGRESS) {
            $html = '<span class="badge badge--info">' . __('En Proceso') . '</span>';
        } elseif ($this->status == self::STATUS_RESOLVED) {
            $html = '<span class="badge badge--success">' . __('Resuelto') . '</span>';
        } elseif ($this->status == self::STATUS_REJECTED) {
            $html = '<span class="badge badge--danger">' . __('Rechazado') . '</span>';
        }
        return $html;
    }

    public function getStatusNameAttribute()
    {
        if ($this->status == self::STATUS_PENDING) {
            return __('Pendiente');
        } elseif ($this->status == self::STATUS_IN_PROGRESS) {
            return __('En Proceso');
        } elseif ($this->status == self::STATUS_RESOLVED) {
            return __('Resuelto');
        } elseif ($this->status == self::STATUS_REJECTED) {
            return __('Rechazado');
        }
        return __('Desconocido');
    }

    public function getTypeNameAttribute()
    {
        return $this->claim_type == self::TYPE_CLAIM ? __('Reclamo') : __('Queja');
    }

    public function getItemTypeNameAttribute()
    {
        return $this->item_type == 1 ? __('Producto') : __('Servicio');
    }
}
