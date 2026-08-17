<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePackage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'amount_paid' => 'double',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function package()
    {
        return $this->belongsTo(BusinessPackage::class, 'package_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if (!$this->expires_at) return true;
        // Compare only dates — a plan expiring today is still active the full calendar day
        return now()->toDateString() <= $this->expires_at->toDateString();
    }
}
