<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerTrial extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function seller() { return $this->belongsTo(Seller::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function package() { return $this->belongsTo(BusinessPackage::class, 'package_id'); }
}
