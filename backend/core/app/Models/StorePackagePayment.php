<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePackagePayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'package_amount' => 'double',
        'gateway_fee'    => 'double',
        'total_amount'   => 'double',
        'paid_at'        => 'datetime',
        'payload'        => 'array',
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

    public function subscription()
    {
        return $this->belongsTo(StorePackage::class, 'store_package_id');
    }
}
