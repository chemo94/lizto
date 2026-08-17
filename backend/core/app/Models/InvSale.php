<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvSale extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_sales';
    protected $casts   = [
        'document_date'       => 'date',
        'subtotal_gravado'    => 'double',
        'subtotal_exonerado'  => 'double',
        'subtotal_inafecto'   => 'double',
        'igv'                 => 'double',
        'total'               => 'double',
    ];

    public function seller()      { return $this->belongsTo(Seller::class); }
    public function supplier()    { return $this->belongsTo(InvSupplier::class, 'supplier_id'); }
    public function cashSession() { return $this->belongsTo(PosCashSession::class, 'cash_session_id'); }

    public function items()
    {
        return $this->hasMany(InvSaleItem::class, 'sale_id');
    }

    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeVoided($q)    { return $q->where('status', 'voided'); }
}
