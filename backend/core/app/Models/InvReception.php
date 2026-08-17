<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvReception extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_receptions';
    protected $casts = [
        'reception_date' => 'date',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(InvPurchaseOrder::class, 'purchase_order_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(InvWarehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(InvReceptionItem::class, 'reception_id');
    }
}
