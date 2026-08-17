<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerReceivableTransaction extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'double', 'post_balance' => 'double'];

    public function seller() { return $this->belongsTo(Seller::class); }
    public function admin() { return $this->belongsTo(Admin::class); }
    public function ref() { return $this->morphTo(); }
}
