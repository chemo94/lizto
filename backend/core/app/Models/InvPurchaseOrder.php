<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvPurchaseOrder extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_purchase_orders';
    protected $casts = [
        'order_date' => 'date',
        'subtotal'   => 'double',
        'igv'        => 'double',
        'total'      => 'double',
    ];

    const STATUS_DRAFT      = 'draft';
    const STATUS_PENDING    = 'pending';
    const STATUS_APPROVED   = 'approved';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED   = 'received';
    const STATUS_CANCELLED  = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT      => 'Borrador',
            self::STATUS_PENDING    => 'Pendiente',
            self::STATUS_APPROVED   => 'Aprobada',
            self::STATUS_IN_TRANSIT => 'En Tránsito',
            self::STATUS_RECEIVED   => 'Recibida',
            self::STATUS_CANCELLED  => 'Anulada',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function supplier()
    {
        return $this->belongsTo(InvSupplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(InvWarehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(InvPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function receptions()
    {
        return $this->hasMany(InvReception::class, 'purchase_order_id');
    }
}
