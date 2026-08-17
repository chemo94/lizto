<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessPackage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'price'         => 'double',
        'duration_days' => 'integer',
        'sort_order'    => 'integer',
        'features'      => 'array',
        'status'        => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function storePackages()
    {
        return $this->hasMany(StorePackage::class, 'package_id');
    }

    public function displayFeatures(): array
    {
        $features = $this->features;

        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = json_last_error() === JSON_ERROR_NONE ? $decoded : array_map('trim', explode(',', $features));
        }

        if (is_array($features) && count($features) === 1 && is_string($features[0]) && str_starts_with(trim($features[0]), '[')) {
            $decoded = json_decode($features[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $features = $decoded;
            }
        }

        return collect((array) $features)
            ->map(function ($feature) {
                if (is_array($feature) || is_object($feature)) {
                    $feature = (object) $feature;
                    $name = trim((string) ($feature->name ?? ''));
                    $value = trim((string) ($feature->value ?? ''));

                    if ($name === '') {
                        return null;
                    }

                    if ($value === '' || in_array(strtolower($value), ['si', 'sí', 'yes', 'true', '1'], true)) {
                        return $name;
                    }

                    return $name . ' (' . $value . ')';
                }

                $feature = trim((string) $feature);
                return $feature !== '' ? $feature : null;
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
