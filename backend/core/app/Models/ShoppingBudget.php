<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingBudget extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'max_product_budget' => 'double',
        'max_delivery_fee'   => 'double',
        'actual_product_cost' => 'double',
        'actual_delivery_fee' => 'double',
    ];

    public function favor()
    {
        return $this->belongsTo(Favor::class);
    }

    public function isOverBudget(): bool
    {
        if (!$this->actual_product_cost || !$this->max_product_budget) {
            return false;
        }
        return $this->actual_product_cost > $this->max_product_budget;
    }

    public function getOverageAmount(): float
    {
        if (!$this->isOverBudget()) return 0;
        return $this->actual_product_cost - $this->max_product_budget;
    }

    public function getEstimatedTotalAttribute(): float
    {
        $productCost = $this->actual_product_cost ?? $this->max_product_budget ?? 0;
        $deliveryFee = $this->actual_delivery_fee ?? $this->max_delivery_fee ?? 0;
        return $productCost + $deliveryFee;
    }
}
