<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosInvoiceType extends Model {
    protected $guarded = ['id']; protected $table = 'pos_invoice_types';
    protected $casts = ['is_electronic'=>'boolean','active'=>'boolean'];
    public function seller(){return $this->belongsTo(Seller::class);}
    public function series(){return $this->hasMany(PosInvoiceSeries::class,'invoice_type_id')->orderBy('series');}
    public function company(){return $this->belongsTo(SellerCompany::class,'seller_company_id');}
}
