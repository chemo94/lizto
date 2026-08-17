<?php namespace App\Models; use App\Traits\GlobalStatus; use Illuminate\Database\Eloquent\Model;
class Coupon extends Model {
    use GlobalStatus;
    protected $guarded = ['id'];
    protected $casts = ['value'=>'double','min_order'=>'double','max_discount'=>'double','starts_at'=>'datetime','expires_at'=>'datetime'];
    public function usages(){ return $this->hasMany(CouponUsage::class); }
    
    public const TYPE_TAXI     = 'taxi';
    public const TYPE_DELIVERY = 'delivery';

    public function scopeForTaxi($query)
    {
        return $query->where('coupon_type', self::TYPE_TAXI);
    }

    public function scopeForDelivery($query)
    {
        return $query->where('coupon_type', self::TYPE_DELIVERY);
    }

    public function isValid($userId = null): bool {
        if (!$this->status) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        if ($userId && $this->per_user_limit) {
            $count = $this->usages()->where('user_id', $userId)->count();
            if ($count >= $this->per_user_limit) return false;
        }
        return true;
    }
    public function calcDiscount($amount): float {
        if ($this->type === 'percentage') { $d = $amount * $this->value / 100; return $this->max_discount ? min($d, $this->max_discount) : $d; }
        if ($this->type === 'fixed') return min($this->value, $amount);
        return 0;
    }
}
class CouponUsage extends Model {
    protected $guarded = ['id']; protected $table = 'coupon_usage';
    public function coupon(){ return $this->belongsTo(Coupon::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
