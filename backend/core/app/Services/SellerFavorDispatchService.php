<?php

namespace App\Services;

use App\Constants\Status;
use App\Events\FavorStatusUpdated;
use App\Models\Driver;
use App\Models\Favor;
use Illuminate\Support\Facades\DB;

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
        $favor->update([
            'courier_id' => null,
            'dispatch_mode' => 'seller_nearby',
            'dispatch_attempted_driver_ids' => [],
            'dispatch_timeout_at' => now(),
        ]);
        return self::dispatchNext($favor->fresh());
    }

    public static function dispatchNext(Favor $favor): ?Driver
    {
        if ($favor->status !== 'searching_courier') return null;

        $attempted = collect($favor->dispatch_attempted_driver_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
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
        $favor->update([
            'courier_id' => $courier->id,
            'dispatch_mode' => $mode,
            'dispatch_attempted_driver_ids' => array_values(array_unique($attempted)),
            'dispatch_timeout_at' => now()->addSeconds(self::RESPONSE_WINDOW_SECONDS),
        ]);

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
        $latColumn = 'COALESCE(NULLIF(current_lat, 0), latitude)';
        $lngColumn = 'COALESCE(NULLIF(current_lot, 0), longitude)';
        $distanceSql = "(6371 * acos(cos(radians(?)) * cos(radians($latColumn)) * cos(radians($lngColumn) - radians(?)) + sin(radians(?)) * sin(radians($latColumn))))";

        return Driver::query()->where('status', Status::ENABLE)->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])->whereHas('wallet', fn ($q) => $q->where('balance', '>', 0))
            // Use the live GPS position when available; otherwise use the
            // courier's registered position so eligible couriers are not skipped.
            ->where(function ($query) {
                $query->where(function ($location) {
                    $location->whereNotNull('current_lat')->whereNotNull('current_lot');
                })->orWhere(function ($location) {
                    $location->whereNotNull('latitude')->whereNotNull('longitude');
                });
            })
            ->when($attempted, fn ($q) => $q->whereNotIn('id', $attempted))
            ->select('drivers.*')->selectRaw("$distanceSql as dispatch_distance", [$lat, $lng, $lat])
            ->when($radius !== null, fn ($q) => $q->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, $radius]))
            ->orderBy('dispatch_distance');
    }
}
