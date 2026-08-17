<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvWarehouseTransferItem extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_warehouse_transfer_items';
    protected $casts = [
        'quantity' => 'double',
    ];

    public function transfer()
    {
        return $this->belongsTo(InvWarehouseTransfer::class, 'transfer_id');
    }

    public function item()
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
