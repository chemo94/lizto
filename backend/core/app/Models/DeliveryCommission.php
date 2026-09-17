<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCommission extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'delivery_percent'         => 'double',
        'favor_percent'            => 'double',
        'min_commission'           => 'double',
        'courier_fixed_amount'     => 'double',
        'tier_inicial_percent'     => 'double',
        'tier_bronce_percent'      => 'double',
        'tier_plata_percent'       => 'double',
        'tier_preferente_percent'  => 'double',
        'store_fixed_amount'       => 'double',
        'store_commission_percent' => 'double',
    ];

    public function getTierInicial(): float
    {
        return (float) ($this->tier_inicial_percent ?? $this->delivery_percent ?? 15.0);
    }

    public function getTierBronce(): float
    {
        return (float) ($this->tier_bronce_percent ?? max(5.0, ($this->delivery_percent ?? 15.0) - 2.0));
    }

    public function getTierPlata(): float
    {
        return (float) ($this->tier_plata_percent ?? max(5.0, ($this->delivery_percent ?? 15.0) - 4.0));
    }

    public function getTierPreferente(): float
    {
        return (float) ($this->tier_preferente_percent ?? 10.0);
    }
}
