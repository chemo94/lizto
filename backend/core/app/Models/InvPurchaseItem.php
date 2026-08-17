<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvPurchaseItem extends Model {
    protected $guarded = ['id'];
    protected $table = 'inv_purchase_items';
    protected $casts = [
        'quantity' => 'double',
        'unit_cost' => 'double',
        'total' => 'double'
    ];
    
    public function purchase(){ 
        return $this->belongsTo(InvPurchase::class); 
    }
    
    public function item(){ 
        return $this->belongsTo(InvItem::class, 'item_id'); 
    }
}
