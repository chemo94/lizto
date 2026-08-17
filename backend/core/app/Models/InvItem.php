<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvItem extends Model {
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $table   = 'inv_items';
    protected $casts   = [
        'stock'        => 'double',
        'min_stock'    => 'double',
        'cost'         => 'double',
        'last_cost'    => 'double',
        'sale_price'   => 'double',
        'is_bar_item'  => 'boolean',
    ];

    const TYPE_INSUMO   = 'insumo';   // entra por compras, sale por producción/merma
    const TYPE_PRODUCTO = 'producto'; // compra-venta directa (tienda)

    const TAX_GRAVADO   = 'gravado';
    const TAX_EXONERADO = 'exonerado';
    const TAX_INAFECTO  = 'inafecto';

    public static function taxTypes(): array
    {
        return [
            self::TAX_GRAVADO   => 'Gravado (IGV 18%)',
            self::TAX_EXONERADO => 'Exonerado',
            self::TAX_INAFECTO  => 'Inafecto',
        ];
    }

    public static function itemTypes(): array
    {
        return [
            self::TYPE_INSUMO   => 'Insumo / Materia prima',
            self::TYPE_PRODUCTO => 'Producto para venta directa',
        ];
    }

    public static function barCategories(): array
    {
        return ['licor', 'mixer', 'garnish', 'preparado', 'otros'];
    }

    public function getTaxLabelAttribute(): string
    {
        return match($this->tax_type) {
            self::TAX_EXONERADO => 'Exonerado',
            self::TAX_INAFECTO  => 'Inafecto',
            default             => 'Gravado',
        };
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function seller()    { return $this->belongsTo(Seller::class); }
    public function kardex()    { return $this->hasMany(InvKardex::class, 'item_id')->latest(); }
    public function wastes()    { return $this->hasMany(InvWaste::class, 'item_id'); }
    public function saleItems() { return $this->hasMany(InvSaleItem::class, 'item_id'); }
    
    public function warehouseStocks()
    {
        return $this->hasMany(InvWarehouseStock::class, 'item_id');
    }

    public function stockInWarehouse($warehouseId)
    {
        return $this->warehouseStocks()->where('warehouse_id', $warehouseId)->value('stock') ?? 0;
    }

    public function recipeItems()
    {
        return $this->hasMany(InvRecipeItem::class, 'item_id');
    }

    public function purchases()
    {
        return $this->belongsToMany(InvPurchase::class, 'inv_purchase_items', 'item_id', 'purchase_id')
                    ->withPivot('quantity', 'unit_cost', 'total');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($q)    { return $q->where('status', 'active'); }
    public function scopeInsumos($q)   { return $q->where('item_type', self::TYPE_INSUMO); }
    public function scopeProductos($q) { return $q->where('item_type', self::TYPE_PRODUCTO); }
    public function scopeBarItems($q)  { return $q->where('is_bar_item', true); }

    public function scopeGravado($q)   { return $q->where('tax_type', self::TAX_GRAVADO); }
    public function scopeExonerado($q) { return $q->where('tax_type', self::TAX_EXONERADO); }
    public function scopeInafecto($q)  { return $q->where('tax_type', self::TAX_INAFECTO); }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->min_stock > 0 && $this->stock <= $this->min_stock;
    }

    public function sunatTaxCode(): string
    {
        return match($this->tax_type) {
            self::TAX_EXONERADO => '20',
            self::TAX_INAFECTO  => '30',
            default             => '10',
        };
    }
}
