<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['unit_price' => 'double', 'total_price' => 'double', 'quantity' => 'integer', 'is_takeaway' => 'boolean', 'tax_type' => 'string'];

    public function order()   { return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
