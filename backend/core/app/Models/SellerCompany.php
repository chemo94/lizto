<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerCompany extends Model
{
    protected $table = 'seller_companies';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'has_bar'   => 'boolean',
        'latitude'  => 'double',
        'longitude' => 'double',
    ];

    const TAX_GRAVADO   = 'gravado';
    const TAX_EXONERADO = 'exonerado';
    const TAX_INAFECTO  = 'inafecto';

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function invoiceTypes()
    {
        return $this->hasMany(PosInvoiceType::class, 'seller_company_id');
    }

    public function invoiceSeries()
    {
        return $this->hasMany(PosInvoiceSeries::class, 'seller_company_id');
    }

    public function sunatInvoices()
    {
        return $this->hasMany(SunatInvoice::class, 'seller_company_id');
    }
}
