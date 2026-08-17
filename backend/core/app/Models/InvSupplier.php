<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvSupplier extends Model {
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $table = 'inv_suppliers';
    
    public function seller(){ 
        return $this->belongsTo(Seller::class); 
    }
    public function purchases(){ 
        return $this->hasMany(InvPurchase::class); 
    }
    public function purchaseOrders(){ 
        return $this->hasMany(InvPurchaseOrder::class); 
    }
}
