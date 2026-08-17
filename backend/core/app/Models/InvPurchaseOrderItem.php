<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvPurchaseOrderItem extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_purchase_order_items';
    protected $casts = [
        'quantity'  => 'double',
        'unit_cost' => 'double',
        'total'     => 'double',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(InvPurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
