<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvWarehouseTransfer extends Model
{
    protected $guarded = ['id'];
    protected $table = 'inv_warehouse_transfers';
    protected $casts = [
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_SENT      = 'sent';
    const STATUS_RECEIVED  = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => 'Pendiente',
            self::STATUS_SENT      => 'Enviado',
            self::STATUS_RECEIVED  => 'Recibido',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(InvWarehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(InvWarehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(InvWarehouseTransferItem::class, 'transfer_id');
    }
}
