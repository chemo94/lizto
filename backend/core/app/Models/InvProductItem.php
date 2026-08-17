<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvProductItem extends Model {
    protected $guarded = ['id'];
    protected $table = 'inv_product_items';
    protected $casts = [
        'quantity' => 'double'
    ];
    
    public function product(){ 
        return $this->belongsTo(Product::class); 
    }
    
    public function item(){ 
        return $this->belongsTo(InvItem::class, 'item_id'); 
    }
}
