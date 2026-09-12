<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Favor;
use App\Models\DeliveryOrder;
use App\Events\FavorStatusUpdated;
use App\Services\FcmService;

class AutoDispatchService
{
    /**
     * Dispatch timeout in seconds before falling back to next courier.
     */
    const DISPATCH_TIMEOUT = 30;

    /**
     * Scoring weights for courier selection.
     */
    const WEIGHTS = [
        'distance'       => 0.40,
        'rating'         => 0.30,
        'acceptance'     => 0.20,
        'current_load'   => 0.10,
    ];

    /**
     * Auto-dispatch a favor to the best available courier.
     * Returns the assigned Driver or null if no courier found.
     */
    public static function dispatchFavor(Favor $favor): ?Driver
    {
        $pickupLat = (float) $favor->pickup_lat;
        $pickupLng = (float) $favor->pickup_lng;

        if (!$pickupLat || !$pickupLng) {
            return null;
        }

        $radius = (float) (gs('delivery_coverage_radius') ?? 10);
        $couriers = self::findNearbyCouriers($pickupLat, $pickupLng, $radius);

        if ($couriers->isEmpty()) {
            return null;
        }

        $scored = $couriers->map(function ($courier) use ($pickupLat, $pickupLng) {
            return [
                'courier' => $courier,
                'score'   => self::scoreCourier($courier, $pickupLat, $pickupLng),
            ];
        })->sortByDesc('score')->values();

        // Try top courier with timeout
        foreach ($scored as $candidate) {
            $courier = $candidate['courier'];

            // Assign directly
            $favor->update([
                'courier_id'         => $courier->id,
                'status'             => 'accepted',
                'courier_assigned_at' => now(),
                'dispatch_mode'      => 'auto',
            ]);

            event(new FavorStatusUpdated($favor));

            $distanceKm = null;
            if ($favor->pickup_lat && $favor->pickup_lng && $favor->delivery_lat && $favor->delivery_lng) {
                $distanceKm = \App\Support\DeliveryPricing::distanceKm((float)$favor->pickup_lat, (float)$favor->pickup_lng, (float)$favor->delivery_lat, (float)$favor->delivery_lng);
            }
            $shortLabel = ($distanceKm !== null && $distanceKm < 1.0) ? ' ⚡ (Envío super corto < 1 km)' : '';

            // Notify the assigned courier
            FcmService::sendToDriver(
                $courier,
                'Envío asignado automáticamente' . $shortLabel,
                'Se te ha asignado el envío #' . $favor->order_no . ' — S/ ' . number_format($favor->delivery_fee ?? 0, 2) . ($distanceKm ? ' (' . round($distanceKm, 2) . ' km)' : ''),
                [
                    'type'              => 'new_delivery_request',
                    'favor_id'          => (string) $favor->id,
                    'is_short_distance' => ($distanceKm !== null && $distanceKm < 1.0) ? '1' : '0',
                    'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            // Notify other couriers that the job is taken
            FcmService::sendToAllCouriers(
                'Envío tomado',
                'El envío #' . $favor->order_no . ' fue asignado a otro repartidor.',
                ['type' => 'favor_taken', 'favor_id' => (string) $favor->id]
            );

            return $courier;
        }

        return null;
    }

    /**
     * Auto-dispatch a delivery order to the best available courier.
     */
    public static function dispatchDeliveryOrder(DeliveryOrder $order): ?Driver
    {
        $store = $order->store;
        if (!$store || !$store->latitude || !$store->longitude) {
            return null;
        }

        $pickupLat = (float) $store->latitude;
        $pickupLng = (float) $store->longitude;
        $radius = (float) (gs('delivery_coverage_radius') ?? 10);

        $couriers = self::findNearbyCouriers($pickupLat, $pickupLng, $radius);

        if ($couriers->isEmpty()) {
            return null;
        }

        $scored = $couriers->map(function ($courier) use ($pickupLat, $pickupLng) {
            return [
                'courier' => $courier,
                'score'   => self::scoreCourier($courier, $pickupLat, $pickupLng),
            ];
        })->sortByDesc('score')->values();

        foreach ($scored as $candidate) {
            $courier = $candidate['courier'];

            $order->update([
                'driver_id'          => $courier->id,
                'status'             => 'on_way',
                'driver_assigned_at' => now(),
                'dispatch_mode'      => 'auto',
            ]);

            event(new DeliveryOrderStatusUpdated($order->fresh('store', 'user')));

            FcmService::sendToDriver(
                $courier,
                'Pedido asignado automáticamente',
                'Se te ha asignado el pedido #' . $order->order_no . ' en ' . ($store->name ?? ''),
                [
                    'type'      => 'new_delivery_request',
                    'order_id'  => (string) $order->id,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );

            return $courier;
        }

        return null;
    }

    /**
     * Find nearby online couriers using Uber H3 + Redis spatial indexing.
     */
    private static function findNearbyCouriers(float $lat, float $lng, float $radiusKm)
    {
        $h3Couriers = \App\Services\H3\DriverGeoRedisService::findNearbyCouriers($lat, $lng, $radiusKm, limit: 10);
        if ($h3Couriers->isNotEmpty()) {
            $ids = $h3Couriers->pluck('id')->all();
            $drivers = Driver::whereIn('id', $ids)->get()->keyBy('id');

            return $h3Couriers->map(function ($hc) use ($drivers) {
                $d = $drivers->get($hc['id']);
                if ($d) {
                    $d->distance_km = $hc['distance_km'];
                }
                return $d;
            })->filter()->values();
        }

        return collect();
    }

    /**
     * Score a courier based on multiple factors.
     * Returns a score between 0 and 1 (higher is better).
     */
    private static function scoreCourier(Driver $courier, float $pickupLat, float $pickupLng): float
    {
        // 1. Distance score (closer = better, max 5km considered)
        $courierLat = (float) ($courier->current_lat ?? $courier->latitude);
        $courierLng = (float) ($courier->current_lot ?? $courier->longitude);

        $distanceKm = isset($courier->distance_km)
            ? (float) $courier->distance_km
            : \App\Services\H3\H3Grid::distanceKm($pickupLat, $pickupLng, $courierLat, $courierLng);

        $distanceScore = max(0, 1 - ($distanceKm / 5));

        // 2. Rating score (1-5 scale normalized to 0-1)
        $rating = (float) ($courier->rating ?? 4.0);
        $ratingScore = $rating / 5;

        // 3. Acceptance rate (percentage of accepted jobs)
        $totalJobs = $courier->total_deliveries ?? 0;
        $acceptedJobs = $courier->completed_deliveries ?? $totalJobs;
        $acceptanceRate = $totalJobs > 0 ? ($acceptedJobs / $totalJobs) : 0.8;
        $acceptanceScore = min(1, $acceptanceRate);

        // 4. Current load (fewer active jobs = better)
        $activeJobs = Favor::where('courier_id', $courier->id)
            ->whereIn('status', ['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery'])
            ->count();
        $loadScore = max(0, 1 - ($activeJobs * 0.33)); // 0 active = 1.0, 3+ active = 0

        // Weighted score
        $score = ($distanceScore * self::WEIGHTS['distance'])
            + ($ratingScore * self::WEIGHTS['rating'])
            + ($acceptanceScore * self::WEIGHTS['acceptance'])
            + ($loadScore * self::WEIGHTS['current_load']);

        return round($score, 4);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
