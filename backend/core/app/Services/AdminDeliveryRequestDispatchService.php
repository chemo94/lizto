<?php

namespace App\Services;

use App\Constants\Status;
use App\Events\FavorStatusUpdated;
use App\Models\Driver;
use App\Models\Favor;
use Illuminate\Support\Facades\DB;

class AdminDeliveryRequestDispatchService
{
    public const INITIAL_BROADCAST_WAIT_SECONDS = 60;
    public const TARGETED_WAIT_SECONDS = 30;

    /**
     * Rotate requests whose current response window has elapsed.
     */
    public static function processDueRequests(): int
    {
        return DB::transaction(function () {
            $favors = Favor::query()
                ->where('status', 'searching_courier')
                ->whereIn('dispatch_mode', ['admin_broadcast', 'admin_targeted'])
                ->whereNotNull('dispatch_timeout_at')
                ->where('dispatch_timeout_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($favors as $favor) {
                self::targetNextCourier($favor);
            }

            return $favors->count();
        });
    }

    /**
     * Sends the request to one new available courier for a 30-second window.
     */
    public static function targetNextCourier(Favor $favor): ?Driver
    {
        if ($favor->status !== 'searching_courier') {
            return null;
        }

        if ($favor->dispatch_mode === 'admin_broadcast') {
            CourierOfferTracker::expireOpen($favor);
        } elseif ($favor->courier_id) {
            CourierOfferTracker::expireOpen($favor, (int) $favor->courier_id);
        }

        $attemptedIds = collect($favor->dispatch_attempted_driver_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $courier = null;

        // 1. Try finding nearest courier via Uber H3 + Redis spatial indexing
        if ($favor->pickup_lat && $favor->pickup_lng) {
            $nearbyCouriers = \App\Services\H3\DriverGeoRedisService::findNearbyCouriers(
                (float) $favor->pickup_lat,
                (float) $favor->pickup_lng,
                radiusKm: (float) (gs('delivery_coverage_radius') ?? 10),
                excludeDriverIds: $attemptedIds,
                limit: 5
            );

            if ($nearbyCouriers->isNotEmpty()) {
                $candidateIds = $nearbyCouriers->pluck('id')->all();
                $courier = Driver::query()
                    ->whereIn('id', $candidateIds)
                    ->where('status', Status::ENABLE)
                    ->where('online_status', 1)
                    ->whereHas('wallet', fn ($query) => $query->where('balance', '>', 0))
                    ->get()
                    ->sortBy(function ($d) use ($candidateIds) {
                        return array_search($d->id, $candidateIds);
                    })
                    ->first();
            }
        }

        // 2. Fallback to standard query if no nearby courier found in range
        if (!$courier) {
            $courier = Driver::query()
                ->where('status', Status::ENABLE)
                ->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])
                ->whereHas('wallet', fn ($query) => $query->where('balance', '>', 0))
                ->when($attemptedIds, fn ($query) => $query->whereNotIn('id', $attemptedIds))
                ->orderBy('id')
                ->first();
        }

        if (!$courier) {
            // Start a new round if every currently available courier was consulted.
            $attemptedIds = [];
            $courier = Driver::query()
                ->where('status', Status::ENABLE)
                ->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])
                ->whereHas('wallet', fn ($query) => $query->where('balance', '>', 0))
                ->orderBy('id')
                ->first();
        }

        if (!$courier) {
            // Keep the request eligible for a future courier coming online.
            $favor->update(['dispatch_timeout_at' => now()->addSeconds(self::TARGETED_WAIT_SECONDS)]);
            return null;
        }

        $attemptedIds[] = $courier->id;
        $favor->update([
            'courier_id'                   => $courier->id,
            'dispatch_mode'                => 'admin_targeted',
            'dispatch_attempted_driver_ids'=> array_values(array_unique($attemptedIds)),
            'dispatch_timeout_at'          => now()->addSeconds(self::TARGETED_WAIT_SECONDS),
        ]);

        $notified = FcmService::sendToDriver(
            $courier,
            '¿Puedes realizar este envío?',
            'Responde al envío #' . $favor->order_no . ' en los próximos 30 segundos.',
            FcmService::courierJobPayload($favor, ['type' => 'targeted_delivery_request'])
        );
        CourierOfferTracker::offered(
            $courier,
            $favor,
            'admin_targeted',
            (bool) $notified,
            $favor->dispatch_timeout_at
        );

        event(new FavorStatusUpdated($favor->fresh(), 'waiting_courier_response'));
        event(new \App\Events\NewJobAvailable($favor->fresh(), '¿Puedes realizar este envío? #' . $favor->order_no));

        return $courier;
    }
}
