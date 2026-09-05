<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use GlobalStatus;

    const STOCK_PACKAGED = 'packaged';
    const STOCK_PREPARED = 'prepared';
    const STOCK_NONE     = 'none';

    public static function stockTypes(): array
    {
        return [
            self::STOCK_PACKAGED => 'Producto empaquetado (devoluble)',
            self::STOCK_PREPARED => 'Preparado/Comida (no devuelve stock)',
            self::STOCK_NONE     => 'Sin inventario',
        ];
    }

    public function isStockPackaged(): bool { return $this->stock_type === self::STOCK_PACKAGED; }
    public function isStockPrepared(): bool { return $this->stock_type === self::STOCK_PREPARED; }
    public function hasStockTracking(): bool { return $this->stock_type !== self::STOCK_NONE; }

    protected $guarded = ['id'];

    protected $casts = [
        'status'         => 'integer',
        'sort_order'     => 'integer',
        'price'          => 'double',
        'discount_price' => 'double',
        'is_promoted'    => 'boolean',
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

    public function getTaxLabelAttribute(): string
    {
        return match($this->tax_type) {
            self::TAX_EXONERADO => 'Exonerado',
            self::TAX_INAFECTO  => 'Inafecto',
            default             => 'Gravado',
        };
    }

    public function sunatTaxCode(): string
    {
        return match($this->tax_type) {
            self::TAX_EXONERADO => '20',
            self::TAX_INAFECTO  => '30',
            default             => '10',
        };
    }

    public function invProductItems()
    {
        return $this->hasMany(InvProductItem::class, 'product_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(StoreCategory::class, 'store_category_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class)->active()->orderBy('sort_order');
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class)->active()->orderBy('sort_order');
    }

    public function finalPrice()
    {
        $discountPrice = (float) ($this->discount_price ?? 0);
        return $discountPrice > 0 ? $discountPrice : (float) $this->price;
    }
}
