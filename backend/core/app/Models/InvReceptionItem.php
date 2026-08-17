<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvReceptionItem extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_reception_items';
    protected $casts = [
        'quantity_ordered'  => 'double',
        'quantity_received' => 'double',
        'quantity_damaged'  => 'double',
        'expiration_date'   => 'date',
    ];

    public function reception()
    {
        return $this->belongsTo(InvReception::class, 'reception_id');
    }

    public function item()
    {
        return $this->belongsTo(InvItem::class, 'item_id');
    }
}
