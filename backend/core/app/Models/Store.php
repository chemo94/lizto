<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use GlobalStatus;

    const TYPE_RESTAURANT  = 'restaurant';
    const TYPE_SUPERMARKET = 'supermarket';
    const TYPE_PHARMACY    = 'pharmacy';
    const TYPE_LIQUOR      = 'liquor_store';
    const TYPE_PET_SHOP    = 'pet_shop';

    public static function types(): array
    {
        return [
            self::TYPE_RESTAURANT  => 'Restaurante',
            self::TYPE_SUPERMARKET => 'Supermercado',
            self::TYPE_PHARMACY    => 'Farmacia',
            self::TYPE_LIQUOR      => 'Licorería',
            self::TYPE_PET_SHOP    => 'Tienda de Mascotas',
        ];
    }

    public function isRestaurant(): bool
    {
        return $this->store_type === self::TYPE_RESTAURANT;
    }

    protected $guarded = ['id'];
    protected $appends = ['is_open_now', 'rating', 'total_orders', 'is_featured', 'is_premium', 'active_packages', 'cover_video_url'];

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
        if (!$this->is_open) return false;

        $todaySchedules = $this->schedules()->where('day', now()->dayOfWeek)->get();
        if ($todaySchedules->isNotEmpty()) {
            $now = now()->format('H:i');
            foreach ($todaySchedules as $s) {
                if ($now >= $s->open_time && $now <= $s->close_time) return true;
            }
            return false;
        }

        if ($this->opening_time && $this->closing_time) {
            $now = now()->format('H:i');
            return $now >= $this->opening_time && $now <= $this->closing_time;
        }

        return true;
    }

    public function getOpeningTimeAttribute($value): ?string { return $value ?? '08:00'; }
    public function getClosingTimeAttribute($value): ?string { return $value ?? '22:00'; }

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

    public function generalCategories()
    {
        return $this->belongsToMany(GeneralCategory::class, 'store_general_category');
    }

    public function subCategories()
    {
        return $this->belongsToMany(SubCategory::class, 'store_sub_category');
    }

    public function menuCategories()
    {
        return $this->hasMany(StoreCategory::class)->active()->orderBy('sort_order');
    }

    public function categories()
    {
        return $this->menuCategories();
    }

    public function products()
    {
        return $this->hasMany(Product::class)->active()->orderBy('sort_order');
    }

    public function stories()
    {
        return $this->hasMany(StoreStory::class)->active()->orderBy('created_at', 'desc');
    }

    public function schedules()
    {
        return $this->hasMany(StoreSchedule::class);
    }

    public function storePackages()
    {
        return $this->hasMany(StorePackage::class);
    }

    public function activePackagesRelation()
    {
        return $this->storePackages()
            ->where('status', 'active')
            ->where(function ($q) {
                // Compare dates only — a plan expiring today is still active for the full day
                $q->whereNull('expires_at')->orWhereRaw('DATE(expires_at) >= ?', [now()->toDateString()]);
            })
            ->with('package');
    }


    public function getIsFeaturedAttribute(): bool
    {
        return $this->activePackagesRelation()->whereHas('package', fn($q) => $q->whereIn('type', ['featured', 'premium']))->exists();
    }

    public function hasPaidPackage(): bool
    {
        return $this->activePackagesRelation()->exists();
    }

    public function getIsPremiumAttribute(): bool
    {
        return $this->hasPaidPackage();
    }

    public function hasPremiumPackage(): bool
    {
        return $this->activePackagesRelation()->exists();
    }

    public function getPlanType(): ?string
    {
        $active = $this->activePackagesRelation()->first();
        return $active?->package?->type;
    }

    public function hasReachedInvoiceLimit(): bool
    {
        $activePackage = $this->activePackagesRelation()
            ->with('package')
            ->first();

        if (!$activePackage || !$activePackage->package) return false;

        $features = (array) ($activePackage->package->features ?? []);
        $limit = null;
        foreach ($features as $f) {
            $fObj = (object) $f;
            if (isset($fObj->key) && $fObj->key === 'invoices') {
                $val = strtolower(trim($fObj->value));
                if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                    return false;
                }
                if (is_numeric($val)) {
                    $limit = (int) $val;
                }
                break;
            }
        }

        if ($limit === null) {
            $limits = ['basic' => 50, 'featured' => 100];
            $type = $activePackage->package->type;
            $limit = $limits[$type] ?? null;
        }

        if ($limit === null) return false;

        $startDate = $activePackage->starts_at ?: $activePackage->created_at;
        $invoiceCount = \App\Models\SunatInvoice::where('seller_id', $this->seller_id)
            ->where('created_at', '>=', $startDate)
            ->whereIn('tipo_doc', ['01', '03', '07', '08', 'RA'])
            ->count();

        return $invoiceCount >= $limit;
    }

    public function getInvoiceLimit(): ?int
    {
        $activePackage = $this->activePackagesRelation()
            ->with('package')
            ->first();

        if (!$activePackage || !$activePackage->package) return null;

        $features = (array) ($activePackage->package->features ?? []);
        foreach ($features as $f) {
            $fObj = (object) $f;
            if (isset($fObj->key) && $fObj->key === 'invoices') {
                $val = strtolower(trim($fObj->value));
                if ($val === 'ilimitado' || $val === 'unlimited' || $val === 'sí' || $val === 'si') {
                    return 999999;
                }
                if (is_numeric($val)) {
                    return (int) $val;
                }
            }
        }

        $limits = ['basic' => 50, 'featured' => 100];
        $type = $activePackage->package->type;
        return $limits[$type] ?? null;
    }

    public function getActivePackagesAttribute(): array
    {
        return $this->relationLoaded('storePackages')
            ? $this->storePackages->filter(fn($sp) => $sp->isActive())->values()->toArray()
            : [];
    }

    public function scopeWithActivePackages($query)
    {
        return $query->with(['storePackages' => function ($q) {
            $q->where('status', 'active')
              ->where(function ($q) {
                  $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
              })
              ->with('package');
        }]);
    }

    public function scopeOrderByFeatured($query)
    {
        return $query->withActivePackages()->orderByRaw(
            '(SELECT COUNT(*) FROM store_packages sp 
              JOIN business_packages bp ON bp.id = sp.package_id 
              WHERE sp.store_id = stores.id AND sp.status = ? 
              AND (sp.expires_at IS NULL OR sp.expires_at > ?) 
              AND bp.type IN (?, ?)) DESC',
            ['active', now(), 'premium', 'featured']
        );
    }

    public function scopeOpen($query)
    {
        return $query->where('is_open', 1);
    }

    public function dispatchWebhook(string $event, array $data): bool
    {
        if (!$this->webhook_url) {
            return false;
        }

        try {
            $payload = [
                'event'     => $event,
                'timestamp' => now()->toIso8601String(),
                'store_id'  => $this->id,
                'data'      => $data,
            ];

            $jsonPayload = json_encode($payload);
            $signature = hash_hmac('sha256', $jsonPayload, $this->webhook_secret);

            $ch = curl_init($this->webhook_url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-LizToGo-Signature: ' . $signature,
                    'User-Agent: LizToGo-Webhook-Dispatcher/1.0',
                ],
            ]);
            
            curl_exec($ch);
            curl_close($ch);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCoverVideoUrlAttribute(): ?string
    {
        if (!$this->cover_video) return null;
        return asset('assets/video/store_cover/' . $this->cover_video);
    }
}
