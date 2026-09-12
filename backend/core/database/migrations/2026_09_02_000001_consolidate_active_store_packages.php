<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_packages')
            ->where('status', 'active')
            ->select('store_id')
            ->groupBy('store_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('store_id')
            ->each(function ($storeId) {
                $subscriptions = DB::table('store_packages')
                    ->where('store_id', $storeId)
                    ->where('status', 'active')
                    ->orderByDesc('created_at')
                    ->get();

                $packageIds = $subscriptions->pluck('package_id')->unique();

                if ($packageIds->count() === 1) {
                    // Legacy renewals created one active future row per payment.
                    // Collapse them while preserving the original start and the
                    // furthest paid expiration date.
                    $keeper = $subscriptions->sortByDesc(function ($subscription) {
                        return $subscription->expires_at === null
                            ? PHP_INT_MAX
                            : strtotime($subscription->expires_at);
                    })->first();

                    $startsAt = $subscriptions->pluck('starts_at')->filter()->sort()->first();
                    $expiresAt = $subscriptions->contains(fn ($subscription) => $subscription->expires_at === null)
                        ? null
                        : $subscriptions->pluck('expires_at')->filter()->sortDesc()->first();

                    DB::table('store_packages')->where('id', $keeper->id)->update([
                        'starts_at' => $startsAt ?: $keeper->created_at,
                        'expires_at' => $expiresAt,
                        'updated_at' => now(),
                    ]);
                } else {
                    // A plan change supersedes older plans; the latest assignment
                    // is the authoritative subscription.
                    $keeper = $subscriptions->first();
                }

                DB::table('store_packages')
                    ->where('store_id', $storeId)
                    ->where('status', 'active')
                    ->where('id', '<>', $keeper->id)
                    ->update(['status' => 'inactive', 'updated_at' => now()]);
            });
    }

    public function down(): void
    {
        // Consolidation intentionally has no automatic rollback: restoring
        // overlapping active subscriptions would reintroduce invalid state.
    }
};
