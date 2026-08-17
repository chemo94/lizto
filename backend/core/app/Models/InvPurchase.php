<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvPurchase extends Model {
    protected $guarded = ['id'];
    protected $table = 'inv_purchases';
    protected $casts = [
        'document_date' => 'date',
        'subtotal' => 'double',
        'igv' => 'double',
        'total' => 'double'
    ];
    
    public function seller(){ 
        return $this->belongsTo(Seller::class); 
    }
    
    public function supplier(){ 
        return $this->belongsTo(InvSupplier::class, 'supplier_id'); 
    }
    
    public function items(){ 
        return $this->hasMany(InvPurchaseItem::class, 'purchase_id'); 
    }
    
    public function cashSession(){ 
        return $this->belongsTo(PosCashSession::class, 'cash_session_id'); 
    }
}
