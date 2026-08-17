<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvWarehouse extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_warehouses';

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function stocks()
    {
        return $this->hasMany(InvWarehouseStock::class, 'warehouse_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
