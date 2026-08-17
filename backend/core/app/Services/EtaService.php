<?php

namespace App\Services;

use App\Models\Favor;
use App\Models\DeliveryOrder;
use App\Events\FavorStatusUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EtaService
{
    const CACHE_PREFIX = 'eta:';
    const CACHE_TTL = 60; // seconds
    const DIRECTIONS_API = 'https://maps.googleapis.com/maps/api/directions/json';

    /**
     * Calculate ETA using Google Directions API with traffic awareness.
     * Returns ['duration_min' => float, 'duration_text' => string, 'distance_text' => string, 'distance_meters' => int]
     */
    public static function calculateEta(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        string $mode = 'driving'
    ): ?array {
        $apiKey = gs('google_maps_api');
        if (!$apiKey) {
            return self::fallbackEta($originLat, $originLng, $destLat, $destLng);
        }

        // Check cache
        $cacheKey = self::CACHE_PREFIX . md5("{$originLat},{$originLng},{$destLat},{$destLng},{$mode}");
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->get(self::DIRECTIONS_API, [
                'origin'      => "{$originLat},{$originLng}",
                'destination'  => "{$destLat},{$destLng}",
                'mode'         => $mode,
                'departure_time' => 'now', // Enables traffic-aware ETA
                'key'          => $apiKey,
            ]);

            if ($response->failed()) {
                return self::fallbackEta($originLat, $originLng, $destLat, $destLng);
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['routes'])) {
                return self::fallbackEta($originLat, $originLng, $destLat, $destLng);
            }

            $leg = $data['routes'][0]['legs'][0];

            // Use duration_in_traffic if available (traffic-aware), else regular duration
            $duration = $leg['duration_in_traffic'] ?? $leg['duration'];
            $distance = $leg['distance'];

            $result = [
                'duration_min'    => round($duration['value'] / 60, 1),
                'duration_text'   => $duration['text'],
                'distance_text'   => $distance['text'],
                'distance_meters' => $distance['value'],
                'source'          => isset($leg['duration_in_traffic']) ? 'google_traffic' : 'google',
            ];

            // Cache for 60 seconds
            Cache::put($cacheKey, $result, self::CACHE_TTL);

            return $result;
        } catch (\Throwable $e) {
            return self::fallbackEta($originLat, $originLng, $destLat, $destLng);
        }
    }

    /**
     * Calculate ETA for a favor based on its current status.
     * Returns ETA from courier to pickup, or pickup to delivery.
     */
    public static function forFavor(Favor $favor): ?array
    {
        if (!$favor->courier) {
            return null;
        }

        $courierLat = (float) $favor->courier->latitude;
        $courierLng = (float) $favor->courier->longitude;

        if (!$courierLat || !$courierLng) {
            return null;
        }

        $pickupLat = (float) $favor->pickup_lat;
        $pickupLng = (float) $favor->pickup_lng;
        $deliveryLat = (float) $favor->delivery_lat;
        $deliveryLng = (float) $favor->delivery_lng;

        $status = $favor->status;

        if (in_array($status, ['accepted', 'on_way_to_pickup'])) {
            // Courier → Pickup
            return self::calculateEta($courierLat, $courierLng, $pickupLat, $pickupLng);
        } elseif (in_array($status, ['at_pickup', 'on_way_to_delivery'])) {
            // Pickup → Delivery
            return self::calculateEta($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
        }

        return null;
    }

    /**
     * Calculate ETA for a delivery order.
     */
    public static function forDeliveryOrder(DeliveryOrder $order): ?array
    {
        if (!$order->driver || !$order->store) {
            return null;
        }

        $driverLat = (float) $order->driver->latitude;
        $driverLng = (float) $order->driver->longitude;

        if (!$driverLat || !$driverLng) {
            return null;
        }

        $storeLat = (float) $order->store->latitude;
        $storeLng = (float) $order->store->longitude;
        $deliveryLat = (float) $order->delivery_lat;
        $deliveryLng = (float) $order->delivery_lng;

        $status = $order->status;

        if (in_array($status, ['confirmed', 'preparing', 'ready'])) {
            // Driver → Store (pickup)
            return self::calculateEta($driverLat, $driverLng, $storeLat, $storeLng);
        } elseif ($status === 'on_way') {
            // Store → Delivery
            return self::calculateEta($storeLat, $storeLng, $deliveryLat, $deliveryLng);
        }

        return null;
    }

    /**
     * Broadcast ETA update to all subscribers of a favor.
     */
    public static function broadcastFavorEta(Favor $favor): void
    {
        $eta = self::forFavor($favor);
        if ($eta) {
            broadcast()->to("private-favor.{$favor->id}", new \stdClass());
            // Use raw broadcast for ETA-specific data
            \Illuminate\Support\Facades\Broadcast::event(
                "private-favor.{$favor->id}",
                'eta_update',
                [
                    'favor_id'      => $favor->id,
                    'duration_min'  => $eta['duration_min'],
                    'duration_text' => $eta['duration_text'],
                    'distance_text' => $eta['distance_text'],
                    'source'        => $eta['source'],
                ]
            );
        }
    }

    /**
     * Fallback ETA using Haversine distance and average speed.
     * Used when Google API is unavailable.
     */
    private static function fallbackEta(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng
    ): ?array {
        $avgSpeed = (float) (gs('delivery_avg_speed') ?? 20); // km/h

        $distanceKm = DeliveryPricing::distanceKm($originLat, $originLng, $destLat, $destLng);
        $durationMin = max(1, ($distanceKm / max($avgSpeed, 1)) * 60);

        return [
            'duration_min'    => round($durationMin, 1),
            'duration_text'   => round($durationMin) . ' min',
            'distance_text'   => round($distanceKm, 1) . ' km',
            'distance_meters' => (int) ($distanceKm * 1000),
            'source'          => 'fallback',
        ];
    }

    /**
     * Get estimated total delivery time (pickup + delivery legs).
     */
    public static function getTotalEstimate(Favor $favor): ?array
    {
        $pickupLat = (float) $favor->pickup_lat;
        $pickupLng = (float) $favor->pickup_lng;
        $deliveryLat = (float) $favor->delivery_lat;
        $deliveryLng = (float) $favor->delivery_lng;

        // Pickup → Delivery estimate
        $deliveryEta = self::calculateEta($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);

        if (!$deliveryEta) {
            return null;
        }

        // Add estimated pickup time (based on average courier response)
        $avgPickupTime = 8; // minutes average for courier to arrive at pickup

        return [
            'pickup_estimate_min'  => $avgPickupTime,
            'delivery_estimate_min' => $deliveryEta['duration_min'],
            'total_estimate_min'   => $avgPickupTime + $deliveryEta['duration_min'],
            'total_estimate_text'  => 'Approx. ' . round($avgPickupTime + $deliveryEta['duration_min']) . ' min',
            'delivery_distance'    => $deliveryEta['distance_text'],
            'source'               => $deliveryEta['source'],
        ];
    }
}
