<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class SellerDeliveryOrderDispatchService
{
    private const RESPONSE_WINDOW_SECONDS = 30;
    private const RETRY_WINDOW_SECONDS = 60;

    public static function start(DeliveryOrder $order, array $excludedDriverIds = []): void
    {
        if ($order->status !== 'ready' || $order->driver_id) return;
        if (in_array($order->dispatch_mode, ['seller_nearby', 'seller_expanded', 'seller_exhausted'], true)) return;

        $order->update([
            'courier_id' => null,
            'dispatch_mode' => 'seller_nearby',
            'dispatch_attempted_driver_ids' => array_values(array_unique(array_map('intval', $excludedDriverIds))),
            'dispatch_timeout_at' => now(),
        ]);

        self::dispatchNext($order->fresh(['store', 'user']));
    }

    public static function processDueOrders(): int
    {
        $orders = DeliveryOrder::where('status', 'ready')
            ->whereNull('driver_id')
            ->whereIn('dispatch_mode', ['seller_nearby', 'seller_expanded', 'seller_exhausted'])
            ->whereNotNull('dispatch_timeout_at')
            ->where('dispatch_timeout_at', '<=', now())
            ->orderBy('dispatch_timeout_at')
            ->limit(100)
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                $locked = DeliveryOrder::whereKey($order->id)->lockForUpdate()->first();
                if ($locked && $locked->status === 'ready' && !$locked->driver_id
                    && $locked->dispatch_timeout_at?->lte(now())) {
                    self::dispatchNext($locked->fresh(['store', 'user']));
                }
            });
        }

        return $orders->count();
    }

    public static function dispatchNext(DeliveryOrder $order): ?Driver
    {
        if ($order->status !== 'ready' || $order->driver_id) return null;

        if ($order->courier_id) {
            CourierOfferTracker::expireOpen($order, (int) $order->courier_id);
        }

        $store = $order->store;
        $lat = (float) ($store?->latitude ?? 0);
        $lng = (float) ($store?->longitude ?? 0);
        $attempted = collect($order->dispatch_attempted_driver_ids ?? [])
            ->map(fn ($id) => (int) $id)->filter()->values()->all();

        if ($lat && $lng) {
            $distanceSql = '6371 * acos(LEAST(1, GREATEST(-1, cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lot) - radians(?)) + sin(radians(?)) * sin(radians(current_lat)))))';
            $base = Driver::query()
                ->where('status', Status::ENABLE)
                ->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])
                ->whereNotNull('current_lat')->whereNotNull('current_lot')
                ->where('current_lat', '!=', 0)->where('current_lot', '!=', 0)
                ->when($attempted, fn ($q) => $q->whereNotIn('id', $attempted))
                ->select('drivers.*')
                ->selectRaw("$distanceSql as dispatch_distance", [$lat, $lng, $lat]);

            $courier = (clone $base)->whereRaw("$distanceSql <= ?", [$lat, $lng, $lat, 5])
                ->orderBy('dispatch_distance')->first();
            $mode = 'seller_nearby';
            if (!$courier) {
                $courier = (clone $base)->orderBy('dispatch_distance')->first();
                $mode = 'seller_expanded';
            }

            if ($courier) {
                $attempted[] = (int) $courier->id;
                $expiresAt = now()->addSeconds(self::RESPONSE_WINDOW_SECONDS);
                $order->update([
                    'courier_id' => $courier->id,
                    'dispatch_mode' => $mode,
                    'dispatch_attempted_driver_ids' => array_values(array_unique($attempted)),
                    'dispatch_timeout_at' => $expiresAt,
                ]);

                $sent = FcmService::sendToDriver(
                    $courier,
                    'Pedido listo para recojo',
                    '¿Puedes recoger el pedido #' . $order->order_no . ' en ' . ($store->name ?? 'la tienda') . '? Tienes 30 segundos para responder.',
                    FcmService::courierJobPayload($order->fresh(['store', 'user']), ['dispatch_mode' => $mode])
                );
                CourierOfferTracker::offered($courier, $order, $mode, (bool) $sent, $expiresAt);
                return $courier;
            }
        }

        // Recheck online couriers periodically if nobody is currently available.
        // Reset attempts after a full pass so couriers can receive a later offer.
        $order->update([
            'courier_id' => null,
            'dispatch_mode' => 'seller_exhausted',
            'dispatch_attempted_driver_ids' => [],
            'dispatch_timeout_at' => now()->addSeconds(self::RETRY_WINDOW_SECONDS),
        ]);

        return null;
    }
}
