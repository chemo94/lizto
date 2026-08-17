<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvSaleItem extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_sale_items';
    protected $casts   = [
        'quantity'   => 'double',
        'unit_price' => 'double',
        'subtotal'   => 'double',
        'igv'        => 'double',
        'total'      => 'double',
    ];

    const TAX_GRAVADO   = 'gravado';
    const TAX_EXONERADO = 'exonerado';
    const TAX_INAFECTO  = 'inafecto';

    public static function taxTypes(): array
    {
        return [
            self::TAX_GRAVADO   => 'Gravado (IGV 18%)',
            self::TAX_EXONERADO => 'Exonerado (sin IGV)',
            self::TAX_INAFECTO  => 'Inafecto (sin IGV)',
        ];
    }

    /** SUNAT afectacion IGV code */
    public function sunatTaxCode(): string
    {
        return match($this->tax_type) {
            self::TAX_EXONERADO => '20',
            self::TAX_INAFECTO  => '30',
            default             => '10',
        };
    }

    public function sale() { return $this->belongsTo(InvSale::class, 'sale_id'); }
    public function item() { return $this->belongsTo(InvItem::class, 'item_id'); }
}
