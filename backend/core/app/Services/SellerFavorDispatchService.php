<?php

namespace App\Services;

use App\Constants\Status;
use App\Events\FavorStatusUpdated;
use App\Models\Driver;
use App\Models\Favor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SellerFavorDispatchService
{
    public const RESPONSE_WINDOW_SECONDS = 15;
    public const BROADCAST_WINDOW_SECONDS = 15;

    public static function processDueRequests(): int
    {
        return DB::transaction(function () {
            $favors = Favor::where('status', 'searching_courier')
                ->whereIn('dispatch_mode', ['seller_broadcast', 'seller_nearby', 'seller_expanded'])
                ->whereNotNull('dispatch_timeout_at')
                ->where('dispatch_timeout_at', '<=', now())
                ->lockForUpdate()->get();
            foreach ($favors as $favor) self::dispatchNext($favor);
            return $favors->count();
        });
    }

    public static function start(Favor $favor): ?Driver
    {
        $dispatchState = [
            'courier_id' => null,
            'dispatch_mode' => 'seller_broadcast',
            'dispatch_timeout_at' => now()->addSeconds(self::BROADCAST_WINDOW_SECONDS),
        ];
        if (self::tracksAttemptedDrivers()) {
            $dispatchState['dispatch_attempted_driver_ids'] = [];
        }
        $favor->update($dispatchState);

        $driverIds = self::broadcastCouriers()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($driverIds) {
            foreach (Driver::whereIn('id', $driverIds)->get() as $driver) {
                $notified = FcmService::sendToDriver($driver, 'Nuevo envío disponible',
                    'Solicitud #' . $favor->order_no . ' — S/ ' . number_format($favor->total ?? $favor->delivery_fee ?? 0, 2) . '. Responde antes de que inicie la asignación por cercanía.',
                    FcmService::courierJobPayload($favor));
                CourierOfferTracker::offered($driver, $favor, 'seller_broadcast', (bool) $notified, $favor->dispatch_timeout_at);
            }
        }

        event(new \App\Events\NewJobAvailable($favor->fresh(), 'Nuevo envío disponible #' . $favor->order_no));
        event(new FavorStatusUpdated($favor->fresh(), 'broadcast_to_online_couriers'));
        return null;
    }

    public static function dispatchNext(Favor $favor): ?Driver
    {
        if ($favor->status !== 'searching_courier') return null;

        CourierOfferTracker::expireOpen($favor, $favor->courier_id ? (int) $favor->courier_id : null);

        $attempted = self::tracksAttemptedDrivers()
            ? collect($favor->dispatch_attempted_driver_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all()
            : [];
        $nearbyRadius = min(5, (float) (gs('delivery_coverage_radius') ?? 10));
        $courier = self::availableCouriers($favor, $attempted, $nearbyRadius)->first();
        $mode = 'seller_nearby';

        if (!$courier) {
            $courier = self::availableCouriers($favor, $attempted, null)->first();
            $mode = 'seller_expanded';
        }

        if (!$courier) {
            $favor->update(['courier_id' => null, 'dispatch_mode' => 'seller_exhausted', 'dispatch_timeout_at' => null]);
            event(new FavorStatusUpdated($favor->fresh(), 'search_exhausted'));
            return null;
        }

        $attempted[] = $courier->id;
        $dispatchState = [
            'courier_id' => $courier->id,
            'dispatch_mode' => $mode,
            'dispatch_timeout_at' => now()->addSeconds(self::RESPONSE_WINDOW_SECONDS),
        ];
        if (self::tracksAttemptedDrivers()) {
            $dispatchState['dispatch_attempted_driver_ids'] = array_values(array_unique($attempted));
        }
        $favor->update($dispatchState);

        $notified = FcmService::sendToDriver($courier, '¿Puedes realizar este envío?',
            'Tienes 15 segundos para responder al envío #' . $favor->order_no . '.',
            FcmService::courierJobPayload($favor, ['type' => 'seller_targeted_favor']));
        CourierOfferTracker::offered($courier, $favor, $mode, (bool) $notified, $favor->dispatch_timeout_at);
        event(new FavorStatusUpdated($favor->fresh(), 'waiting_courier_response'));
        return $courier;
    }

    private static function availableCouriers(Favor $favor, array $attempted, ?float $radius)
    {
        $lat = (float) $favor->pickup_lat;
        $lng = (float) $favor->pickup_lng;
        // Driver live coordinates are stored in current_lat/current_lot.
        // The drivers table has no latitude/longitude fallback columns.
        $latColumn = 'current_lat';
        $lngColumn = 'current_lot';
        $distanceSql = "(6371 * acos(cos(radians(?)) * cos(radians($latColumn)) * cos(radians($lngColumn) - radians(?)) + sin(radians(?)) * sin(radians($latColumn))))";

        return Driver::query()->where('status', Status::ENABLE)->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])
            ->whereHas('wallet', fn ($q) => $q->where('balance', '>', 0))
            ->whereNotNull('current_lat')->whereNotNull('current_lot')
            ->where('current_lat', '!=', 0)->where('current_lot', '!=', 0)
            ->when($attempted, fn ($q) => $q->whereNotIn('id', $attempted))
            ->select('drivers.*')->selectRaw("$distanceSql as dispatch_distance", [$lat, $lng, $lat])
            ->when($radius !== null, fn ($q) => $q->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radius]))
            ->orderBy('dispatch_distance');
    }

    private static function broadcastCouriers()
    {
        return Driver::query()
            ->where('status', Status::ENABLE)
            ->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])
            ->whereHas('wallet', fn ($q) => $q->where('balance', '>', 0))
            ->get();
    }

    private static function tracksAttemptedDrivers(): bool
    {
        static $available;
        return $available ??= Schema::hasColumn('favors', 'dispatch_attempted_driver_ids');
    }
}
