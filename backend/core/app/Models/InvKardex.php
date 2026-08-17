<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvKardex extends Model {
    protected $guarded = ['id'];
    protected $table = 'inv_kardex';
    protected $casts = [
        'quantity' => 'double',
        'unit_cost' => 'double',
        'total_cost' => 'double',
        'balance_stock' => 'double'
    ];
    
    public function seller(){ 
        return $this->belongsTo(Seller::class); 
    }
    
    public function item(){ 
        return $this->belongsTo(InvItem::class, 'item_id'); 
    }
}
