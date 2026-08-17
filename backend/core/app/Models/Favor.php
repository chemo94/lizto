<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favor extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'estimated_amount'  => 'double',
        'delivery_fee'      => 'double',
        'total'             => 'double',
        'pickup_lat'        => 'double',
        'pickup_lng'        => 'double',
        'delivery_lat'      => 'double',
        'delivery_lng'      => 'double',
        'payment_status'    => 'integer',
        'cash_pay_amount'   => 'double',
        'courier_assigned_at' => 'datetime',
        'delivered_at'      => 'datetime',
        'cancelled_at'      => 'datetime',
        // Sprint 1
        'estimated_minutes' => 'integer',
        'eta_updated_at'    => 'datetime',
        'dispatch_timeout_at' => 'datetime',
        // Sprint 2
        'package_weight_kg' => 'double',
        'item_value'        => 'double',
        'is_fragile'        => 'boolean',
        'scheduled_at'      => 'datetime',
        'dispatch_attempted_driver_ids' => 'array',
        // Sprint 3
        'is_express'        => 'boolean',
        'priority_level'    => 'integer',
        // Sprint 4: Enterprise fields
        'cod_amount'        => 'double',
        'is_heavy'          => 'boolean',
        'is_temperature_controlled' => 'boolean',
        // Return handling
        'return_requested_at' => 'datetime',
        'return_picked_up_at' => 'datetime',
        'return_completed_at' => 'datetime',
        // Shopping
        'actual_total'      => 'double',
        'store_photo_url'   => 'string',
        'receipt_url'       => 'string',
        'stops'             => 'array',
    ];

    protected $appends = ['courier_image_path'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function courier()
    {
        return $this->belongsTo(Driver::class, 'courier_id');
    }

    public function bids()
    {
        return $this->hasMany(FavorBid::class)->orderBy('bid_amount');
    }

    public function acceptedBid()
    {
        return $this->belongsTo(FavorBid::class, 'accepted_bid_id');
    }

    public function messages()
    {
        return $this->hasMany(FavorMessage::class);
    }

    public function review()
    {
        return $this->morphOne(DeliveryReview::class, 'reviewable');
    }

    public function refund()
    {
        return $this->hasOne(DeliveryRefund::class, 'favor_id');
    }

    public function pin()
    {
        return $this->hasOne(DeliveryPin::class, 'favor_id');
    }

    // ── Shopping relationships ──

    public function shoppingItems()
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('sort_order');
    }

    public function budget()
    {
        return $this->hasOne(ShoppingBudget::class);
    }

    public function confirmations()
    {
        return $this->hasMany(ShoppingConfirmation::class);
    }

    // ── Shopping accessors ──

    public function getShoppingProgressAttribute(): float
    {
        $total = $this->shoppingItems()->count();
        if ($total == 0) return 0;
        $confirmed = $this->shoppingItems()
            ->whereIn('status', ['found', 'substituted'])
            ->count();
        return round(($confirmed / $total) * 100, 1);
    }

    public function getNeedsSubstitutionApprovalAttribute(): bool
    {
        return $this->shoppingItems()
            ->where('status', 'not_found')
            ->whereNotNull('substitute_name')
            ->whereNull('customer_approved_at')
            ->count() > 0;
    }

    public function getShoppingSummaryAttribute(): array
    {
        $items = $this->shoppingItems;
        return [
            'total_items'   => $items->count(),
            'found'         => $items->where('status', 'found')->count(),
            'not_found'     => $items->where('status', 'not_found')->count(),
            'substituted'   => $items->where('status', 'substituted')->count(),
            'pending'       => $items->where('status', 'pending')->count(),
            'estimated_total' => $items->sum('total_price'),
            'actual_total'  => $this->actual_total,
        ];
    }

    public function isShoppingType(): bool
    {
        return $this->type === 'buy' && $this->shoppingItems()->exists();
    }

    public function scopeActive($q)
    {
        return $q->whereNotIn('status', ['delivered', 'cancelled']);
    }

    public function getCourierImagePathAttribute()
    {
        return getFilePath('driver');
    }

    /**
     * Check if this favor is eligible for return.
     */
    public function isEligibleForReturn(): bool
    {
        return \App\Services\ReturnHandlingService::isEligibleForReturn($this);
    }

    /**
     * Get the return status label.
     */
    public function getReturnStatusLabelAttribute(): ?string
    {
        $labels = [
            'return_requested'  => 'Devolución solicitada',
            'return_assigned'   => 'Devolución asignada',
            'return_in_transit' => 'Devolución en tránsito',
            'return_completed'  => 'Devolución completada',
        ];

        return $labels[$this->return_status] ?? null;
    }
}
