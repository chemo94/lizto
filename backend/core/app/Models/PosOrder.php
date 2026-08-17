<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosOrder extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];

    protected $casts = [
        'delivery_fee' => 'double', 'subtotal' => 'double',
        'discount' => 'double', 'total' => 'double',
        'delivery_lat' => 'double', 'delivery_lng' => 'double',
        'paid_at' => 'datetime', 'cancelled_at' => 'datetime',
        'payment_details' => 'array',
    ];

    public function seller() { return $this->belongsTo(Seller::class); }
    public function store()  { return $this->belongsTo(Store::class); }
    public function table()  { return $this->belongsTo(PosTable::class, 'pos_table_id'); }
    public function user()   { return $this->belongsTo(User::class); }
    public function items()  { return $this->hasMany(PosOrderItem::class); }
    public function staff()  { return $this->belongsTo(PosStaff::class, 'pos_staff_id'); }
    public function sunatInvoice() { return $this->hasOne(SunatInvoice::class, 'pos_order_id'); }
}
