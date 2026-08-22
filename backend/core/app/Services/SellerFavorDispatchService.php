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

    public static function processDueRequests(): int
    {
        return DB::transaction(function () {
            $favors = Favor::where('status', 'searching_courier')
                ->whereIn('dispatch_mode', ['seller_nearby', 'seller_expanded'])
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
            'dispatch_mode' => 'seller_nearby',
            'dispatch_timeout_at' => now(),
        ];
        if (self::tracksAttemptedDrivers()) {
            $dispatchState['dispatch_attempted_driver_ids'] = [];
        }
        $favor->update($dispatchState);
        return self::dispatchNext($favor->fresh());
    }

    public static function dispatchNext(Favor $favor): ?Driver
    {
        if ($favor->status !== 'searching_courier') return null;

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

        FcmService::sendToDriver($courier, '¿Puedes realizar este envío?',
            'Tienes 15 segundos para responder al envío #' . $favor->order_no . '.', [
                'type' => 'seller_targeted_favor', 'favor_id' => (string) $favor->id,
                'order_no' => $favor->order_no, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);
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
            ->whereIn('service_type', ['delivery', 'both'])->whereHas('wallet', fn ($q) => $q->where('balance', '>', 0))
            ->whereNotNull('current_lat')->whereNotNull('current_lot')
            ->where('current_lat', '!=', 0)->where('current_lot', '!=', 0)
            ->when($attempted, fn ($q) => $q->whereNotIn('id', $attempted))
            ->select('drivers.*')->selectRaw("$distanceSql as dispatch_distance", [$lat, $lng, $lat])
            ->when($radius !== null, fn ($q) => $q->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radius]))
            ->orderBy('dispatch_distance');
    }

    private static function tracksAttemptedDrivers(): bool
    {
        static $available;
        return $available ??= Schema::hasColumn('favors', 'dispatch_attempted_driver_ids');
    }
}
