<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class SunatInvoice extends Model {
    protected $guarded = ['id']; protected $table = 'sunat_invoices';
    protected $casts = ['correlativo'=>'integer','retries'=>'integer','total_gravada'=>'double','total_exonerada'=>'double','total_inafecta'=>'double','total_igv'=>'double','total'=>'double','fecha_emision'=>'datetime','note_adjustments'=>'array','note_processing_at'=>'datetime','cancellation_applied_at'=>'datetime'];

    public function isConsumptionSummary(): bool { return $this->detail_mode === 'consumption'; }

    const STATUS_PENDING   = 'pending';
    const STATUS_GENERATED = 'generated';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_VOIDED    = 'voided';
    const STATUS_ERROR     = 'error';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING   => ['label' => 'Pendiente',  'color' => '#f59e0b'],
            self::STATUS_GENERATED => ['label' => 'Generado',   'color' => '#2563eb'],
            self::STATUS_ACCEPTED  => ['label' => 'Aceptado',   'color' => '#16a34a'],
            self::STATUS_REJECTED  => ['label' => 'Rechazado',  'color' => '#dc2626'],
            self::STATUS_CANCELLED => ['label' => 'Anulado',    'color' => '#6b7280'],
            self::STATUS_VOIDED    => ['label' => 'De Baja',     'color' => '#8b5cf6'],
            self::STATUS_ERROR     => ['label' => 'Error',      'color' => '#dc2626'],
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->cdr_status]['label'] ?? $this->cdr_status;
    }

    public function statusColor(): string
    {
        return self::statuses()[$this->cdr_status]['color'] ?? '#6b7280';
    }

    public function getCdrResponseAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function getSunatResponseAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function getErrorsAttribute($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function seller(){ return $this->belongsTo(Seller::class); }
    public function order(){ return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function company(){ return $this->belongsTo(SellerCompany::class, 'seller_company_id'); }

    public function scopeAccepted($q){ return $q->where('cdr_status', self::STATUS_ACCEPTED); }
    public function scopePending($q){ return $q->where('cdr_status', self::STATUS_PENDING); }
    public function scopeRejected($q){ return $q->where('cdr_status', self::STATUS_REJECTED); }
}
