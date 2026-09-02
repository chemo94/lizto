<?php

namespace App\Services;

use App\Models\BusinessPackage;
use App\Models\Store;
use App\Models\StorePackage;
use Illuminate\Support\Facades\DB;

class StoreSubscriptionService
{
    /**
     * Activates a plan or extends the current plan without creating overlapping
     * active subscriptions. A supplied pending subscription is reused whenever
     * possible and cancelled when its time is consolidated into an active row.
     */
    public function activate(
        Store $store,
        BusinessPackage $package,
        array $attributes = [],
        ?StorePackage $pendingSubscription = null
    ): StorePackage {
        return DB::transaction(function () use ($store, $package, $attributes, $pendingSubscription) {
            $now = now();
            $activeSubscriptions = StorePackage::where('store_id', $store->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            $current = $activeSubscriptions
                ->where('package_id', $package->id)
                ->sortByDesc(fn (StorePackage $subscription) => $subscription->expires_at?->timestamp ?? PHP_INT_MAX)
                ->first();

            if ($current) {
                $baseDate = $current->expires_at && $current->expires_at->isFuture()
                    ? $current->expires_at->copy()
                    : $now->copy();

                $current->fill(array_merge($attributes, [
                    'seller_id' => $store->seller_id,
                    'status' => 'active',
                    'starts_at' => $current->starts_at ?: $now,
                    'expires_at' => $package->duration_days > 0
                        ? $baseDate->addDays($package->duration_days)
                        : null,
                ]))->save();

                StorePackage::where('store_id', $store->id)
                    ->where('status', 'active')
                    ->where('id', '<>', $current->id)
                    ->update(['status' => 'inactive']);

                if ($pendingSubscription && $pendingSubscription->id !== $current->id) {
                    $pendingSubscription->update(['status' => 'cancelled']);
                }

                return $current->refresh();
            }

            StorePackage::where('store_id', $store->id)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);

            $values = array_merge($attributes, [
                'store_id' => $store->id,
                'seller_id' => $store->seller_id,
                'package_id' => $package->id,
                'status' => 'active',
                'starts_at' => $now,
                'expires_at' => $package->duration_days > 0
                    ? $now->copy()->addDays($package->duration_days)
                    : null,
            ]);

            if ($pendingSubscription) {
                $pendingSubscription->fill($values)->save();
                return $pendingSubscription->refresh();
            }

            return StorePackage::create($values);
        });
    }
}
