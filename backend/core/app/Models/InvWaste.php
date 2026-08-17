<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvWaste extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_wastes';
    protected $casts   = [
        'quantity'   => 'double',
        'waste_date' => 'date',
    ];

    public function seller()      { return $this->belongsTo(Seller::class); }
    public function item()        { return $this->belongsTo(InvItem::class, 'item_id'); }
    public function cashSession() { return $this->belongsTo(PosCashSession::class, 'cash_session_id'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeVoided($q) { return $q->where('status', 'voided'); }
}
