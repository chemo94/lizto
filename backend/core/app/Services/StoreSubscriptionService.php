<?php

namespace App\Services;

use App\Models\BusinessPackage;
use App\Models\Store;
use App\Models\StorePackage;
use App\Models\SellerTrial;
use Illuminate\Validation\ValidationException;
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

                $this->syncStoreMode($store, $package, $attributes);
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
                $this->syncStoreMode($store, $package, $attributes);
                return $pendingSubscription->refresh();
            }
            $subscription = StorePackage::create($values);
            $this->syncStoreMode($store, $package, $attributes);
            return $subscription;
        });
    }

    public function startTrial(Store $store, BusinessPackage $package): StorePackage
    {
        return DB::transaction(function () use ($store, $package) {
            if (SellerTrial::where('seller_id', $store->seller_id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['package_id' => 'El primer mes gratis ya fue utilizado.']);
            }

            $subscription = $this->activate($store, $package, [
                'amount_paid' => 0,
                'payment_method' => 'trial',
                'payment_ref' => 'TRIAL-' . $store->seller_id,
                'notes' => 'Primer mes gratis',
            ]);

            SellerTrial::create([
                'seller_id' => $store->seller_id,
                'store_id' => $store->id,
                'package_id' => $package->id,
                'starts_at' => $subscription->starts_at,
                'expires_at' => $subscription->expires_at,
            ]);

            return $subscription;
        });
    }

    private function syncStoreMode(Store $store, BusinessPackage $package, array $attributes): void
    {
        $store->update(['service_mode' => $package->service_mode ?: Store::SERVICE_MODE_RESTAURANT]);
        if (($attributes['payment_method'] ?? null) !== 'trial') {
            SellerTrial::where('seller_id', $store->seller_id)
                ->whereNull('converted_at')->update(['converted_at' => now()]);
        }
    }
}
