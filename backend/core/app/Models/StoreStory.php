<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreStory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'store_id'            => 'integer',
        'duration'            => 'integer',
        'budget'              => 'double',
        'cpi'                 => 'double',
        'total_impressions'   => 'integer',
        'consumed_impressions'=> 'integer',
        'approved_by'         => 'integer',
        'starts_at'           => 'datetime',
        'ends_at'             => 'datetime',
        'approved_at'         => 'datetime',
    ];

    protected $appends = ['media_full_url', 'remaining_budget', 'remaining_impressions'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class, 'store_story_id');
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function getMediaFullUrlAttribute(): ?string
    {
        if (!$this->media_path) return null;
        return asset('assets/images/store_stories/' . $this->media_path);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return round(max(0, $this->budget - ($this->consumed_impressions * $this->cpi)), 2);
    }

    public function getRemainingImpressionsAttribute(): int
    {
        return max(0, $this->total_impressions - $this->consumed_impressions);
    }

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeActive($q)   { return $q->where('status', 'active'); }
    public function scopeRejected($q) { return $q->where('status', 'rejected'); }

    public function scopeVisible($q)
    {
        return $q->whereIn('status', ['active'])
            ->where('consumed_impressions', '<', \DB::raw('total_impressions'))
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->consumed_impressions >= $this->total_impressions
            || ($this->ends_at && now()->gt($this->ends_at));
    }
}
