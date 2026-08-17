<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosInvoiceSeries extends Model {
    protected $guarded = ['id']; protected $table = 'pos_invoice_series';
    protected $casts = ['current_number'=>'integer','max_number'=>'integer','active'=>'boolean'];
    public function invoiceType(){return $this->belongsTo(PosInvoiceType::class,'invoice_type_id');}
    public function seller(){return $this->belongsTo(Seller::class);}
    public function nextNumber(){return str_pad($this->current_number,8,'0',STR_PAD_LEFT);}
    public function company(){return $this->belongsTo(SellerCompany::class,'seller_company_id');}
}
