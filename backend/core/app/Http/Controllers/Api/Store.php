<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeliveryReview;
use App\Models\DeliveryOrder;

class Store extends Model
{
    use GlobalStatus;

    protected $appends = ['is_open_now', 'rating', 'total_orders'];

    protected $casts = [
        'status'           => 'integer',
        'is_open'          => 'integer',
        'delivery_fee'     => 'double',
        'min_order_amount' => 'double',
        'latitude'         => 'double',
        'longitude'        => 'double',
        'preparation_time' => 'integer',
    ];

    public function getIsOpenNowAttribute(): bool
    {
        if (!$this->opening_time || !$this->closing_time) {
            return (bool) $this->is_open;
        }
        $now  = now()->format('H:i');
        return $now >= $this->opening_time && $now <= $this->closing_time;
    }

    public function getOpeningTimeAttribute($value): ?string
    {
        return $value ?? '08:00';
    }

    public function getClosingTimeAttribute($value): ?string
    {
        return $value ?? '22:00';
    }

    public function getRatingAttribute(): float
    {
        return round((float) ($this->deliveryReviews()->avg('rating') ?? 0), 1);
    }

    public function getTotalOrdersAttribute(): int
    {
        return (int) $this->deliveryOrders()->count();
    }

    public function deliveryReviews()
    {
        return $this->hasManyThrough(DeliveryReview::class, DeliveryOrder::class, 'store_id', 'reviewable_id')
            ->where('reviewable_type', DeliveryOrder::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function categories()
    {
        return $this->hasMany(StoreCategory::class)->active()->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class)->active()->orderBy('sort_order');
    }

    public function scopeOpen($query)
    {
        return $query->where('is_open', 1);
    }
}