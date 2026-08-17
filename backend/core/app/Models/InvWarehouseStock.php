<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvWarehouseStock extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_warehouse_stocks';
    protected $casts = [
        'stock' => 'double',
    ];

    public function warehouse()
    {
        return $this->belongsTo(InvWarehouse::class, 'warehouse_id');
    }

    public function item()
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
