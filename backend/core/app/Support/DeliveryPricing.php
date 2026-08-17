<?php

namespace App\Support;

use App\Models\Store;
use App\Models\Zone;
use Illuminate\Http\Request;

class DeliveryPricing
{
    public static function coordinateFromRequest(Request $request, array $latKeys, array $lngKeys): array
    {
        $lat = self::firstCoordinateValue($request, $latKeys);
        $lng = self::firstCoordinateValue($request, $lngKeys);

        return [$lat, $lng];
    }

    public static function deliveryCoordinatesFromRequest(Request $request): array
    {
        return self::coordinateFromRequest(
            $request,
            ['delivery_lat', 'lat', 'latitude'],
            ['delivery_lng', 'lng', 'longitude', 'long']
        );
    }

    public static function hasCoordinates(?float $lat, ?float $lng): bool
    {
        return $lat !== null && $lng !== null;
    }

    /**
     * Uber-style upfront pricing:
     *
     *   fee = (base_fare + distance_fee + time_fee) × surge_multiplier
     *
     *   - base_fare:       delivery_min_fee (primeros base_km incluidos)
     *   - distance_fee:    (distance_km - base_km) × delivery_fee_per_km  (0 si distance <= base_km)
     *   - time_fee:        (distance_km / delivery_avg_speed × 60) × delivery_time_rate
     *   - surge_multiplier: delivery_surge (1.0 = normal, >1 = alta demanda)
     */
    public static function estimateForStore(Store $store, ?float $deliveryLat, ?float $deliveryLng): array
    {
        $baseFare = (float) (gs('delivery_min_fee') ?? 4);
        $perKm    = (float) (gs('delivery_fee_per_km') ?? 1.5);
        $baseKm   = (float) (gs('delivery_base_km') ?? 3);
        $timeRate = (float) (gs('delivery_time_rate') ?? 0.30);
        $avgSpeed = (float) (gs('delivery_avg_speed') ?? 20);
        $surge    = (float) (gs('delivery_surge') ?? 1.00);
        $radius   = (float) (gs('delivery_coverage_radius') ?? 10);

        $noCoords = !self::hasCoordinates($deliveryLat, $deliveryLng) || !$store->latitude || !$store->longitude;

        if ($noCoords) {
            return [
                'delivery_fee'  => self::roundFee($baseFare * $surge),
                'distance_km'   => null,
                'base_fare'     => round($baseFare, 2),
                'distance_fee'  => 0,
                'time_fee'      => 0,
                'time_min'      => null,
                'per_km'        => round($perKm, 2),
                'base_km'       => round($baseKm, 1),
                'time_rate'     => round($timeRate, 2),
                'surge'         => round($surge, 2),
                'in_coverage'   => true,
                'zone'          => null,
            ];
        }

        $distance = self::distanceKm((float) $store->latitude, (float) $store->longitude, $deliveryLat, $deliveryLng);
        $deliveryZone = self::matchingZone($deliveryLat, $deliveryLng);
        $storeZone = self::matchingZone((float) $store->latitude, (float) $store->longitude);
        $hasZones = Zone::active()->exists();
        $inCoverage = $hasZones ? (bool) $deliveryZone && (bool) $storeZone : $distance <= $radius;

        // Distance component: extra km beyond base_km
        $extraKm = max(0, $distance - $baseKm);
        $distanceFee = round($extraKm * $perKm, 2);

        // Time component: estimated minutes × rate/min
        $timeMin = max(1, ($distance / max($avgSpeed, 1)) * 60);
        $timeFee = round($timeMin * $timeRate, 2);

        // Subtotal before surge
        $subtotalFee = $baseFare + $distanceFee + $timeFee;

        // Apply surge multiplier
        $totalFee = self::roundFee($subtotalFee * $surge);

        return [
            'delivery_fee'  => $totalFee,
            'distance_km'   => round($distance, 2),
            'base_fare'     => round($baseFare, 2),
            'distance_fee'  => $distanceFee,
            'time_fee'      => $timeFee,
            'time_min'      => round($timeMin, 1),
            'per_km'        => round($perKm, 2),
            'base_km'       => round($baseKm, 1),
            'time_rate'     => round($timeRate, 2),
            'avg_speed'     => round($avgSpeed, 1),
            'surge'         => round($surge, 2),
            'in_coverage'   => $inCoverage,
            'zone'          => $deliveryZone?->only(['id', 'name']),
        ];
    }

    public static function estimateForPoints(?float $pickupLat, ?float $pickupLng, ?float $deliveryLat, ?float $deliveryLng): array
    {
        $baseFare = (float) (gs('delivery_min_fee') ?? 4);
        $perKm    = (float) (gs('delivery_fee_per_km') ?? 1.5);
        $baseKm   = (float) (gs('delivery_base_km') ?? 3);
        $timeRate = (float) (gs('delivery_time_rate') ?? 0.30);
        $avgSpeed = (float) (gs('delivery_avg_speed') ?? 20);
        $surge    = (float) (gs('delivery_surge') ?? 1.00);
        $radius   = (float) (gs('delivery_coverage_radius') ?? 10);

        return self::estimateInternal($baseFare, $perKm, $baseKm, $timeRate, $avgSpeed, $surge, $radius, $pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
    }

    public static function estimateForFavor(?float $pickupLat, ?float $pickupLng, ?float $deliveryLat, ?float $deliveryLng): array
    {
        $baseFare = (float) (gs('favor_min_fee') ?? 5);
        $perKm    = (float) (gs('favor_fee_per_km') ?? 2);
        $baseKm   = (float) (gs('favor_base_km') ?? 2);
        $timeRate = (float) (gs('favor_time_rate') ?? 0.35);
        $avgSpeed = (float) (gs('favor_avg_speed') ?? 25);
        $surge    = (float) (gs('favor_surge') ?? 1.00);
        $radius   = (float) (gs('favor_coverage_radius') ?? 15);

        return self::estimateInternal($baseFare, $perKm, $baseKm, $timeRate, $avgSpeed, $surge, $radius, $pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
    }

    /**
     * Combines the product budget with the automatically calculated route fee.
     * The route fee already includes time_fee, based on the distance and the
     * courier's configured average speed; customers never choose that time.
     */
    public static function withEstimatedPurchase(array $routeEstimate, float $estimatedAmount): array
    {
        $serviceFee = (float) $routeEstimate['delivery_fee'];

        return array_merge($routeEstimate, [
            'estimated_amount' => round(max(0, $estimatedAmount), 2),
            'service_fee' => $serviceFee,
            'estimated_total' => self::roundFee($serviceFee + max(0, $estimatedAmount)),
        ]);
    }

    /**
     * Multi-stop estimation: Pickup -> Stop 1 -> Stop 2 -> Destination
     */
    public static function estimateForMultiStops(array $points): array
    {
        $baseFare = (float) (gs('favor_min_fee') ?? 5);
        $perKm    = (float) (gs('favor_fee_per_km') ?? 2);
        $baseKm   = (float) (gs('favor_base_km') ?? 2);
        $timeRate = (float) (gs('favor_time_rate') ?? 0.35);
        $avgSpeed = (float) (gs('favor_avg_speed') ?? 25);
        $surge    = (float) (gs('favor_surge') ?? 1.00);

        $validPoints = array_values(array_filter($points, function($pt) {
            return is_array($pt) && isset($pt[0], $pt[1]) && $pt[0] !== null && $pt[1] !== null && is_numeric($pt[0]) && is_numeric($pt[1]);
        }));

        if (count($validPoints) < 2) {
            return self::estimateForFavor(
                $validPoints[0][0] ?? null, $validPoints[0][1] ?? null,
                $validPoints[1][0] ?? null, $validPoints[1][1] ?? null
            );
        }

        $totalDistance = 0;
        for ($i = 0; $i < count($validPoints) - 1; $i++) {
            $totalDistance += self::distanceKm(
                (float) $validPoints[$i][0], (float) $validPoints[$i][1],
                (float) $validPoints[$i+1][0], (float) $validPoints[$i+1][1]
            );
        }

        $extraStopsCount = max(0, count($validPoints) - 2);
        $extraStopsFee = round($extraStopsCount * 2.50, 2);

        $extraKm = max(0, $totalDistance - $baseKm);
        $distanceFee = round($extraKm * $perKm, 2);
        $timeMin = max(1, ($totalDistance / max($avgSpeed, 1)) * 60);
        $timeFee = round($timeMin * $timeRate, 2);

        $subtotalFee = $baseFare + $distanceFee + $timeFee + $extraStopsFee;
        $totalFee = self::roundFee($subtotalFee * $surge);

        return [
            'delivery_fee'      => $totalFee,
            'distance_km'       => round($totalDistance, 2),
            'extra_stops_count' => $extraStopsCount,
            'extra_stops_fee'   => $extraStopsFee,
            'base_fare'         => round($baseFare, 2),
            'distance_fee'      => $distanceFee,
            'time_fee'          => $timeFee,
            'time_min'          => round($timeMin, 1),
            'per_km'            => round($perKm, 2),
            'base_km'           => round($baseKm, 1),
            'time_rate'         => round($timeRate, 2),
            'avg_speed'         => round($avgSpeed, 1),
            'surge'             => round($surge, 2),
            'in_coverage'       => true,
        ];
    }

    private static function estimateInternal(
        float $baseFare, float $perKm, float $baseKm, float $timeRate, float $avgSpeed, float $surge, float $radius,
        ?float $pickupLat, ?float $pickupLng, ?float $deliveryLat, ?float $deliveryLng
    ): array {
        $noCoords = !self::hasCoordinates($pickupLat, $pickupLng) || !self::hasCoordinates($deliveryLat, $deliveryLng);

        if ($noCoords) {
            return [
                'delivery_fee'  => self::roundFee($baseFare * $surge),
                'distance_km'   => null,
                'base_fare'     => round($baseFare, 2),
                'distance_fee'  => 0,
                'time_fee'      => 0,
                'time_min'      => null,
                'per_km'        => round($perKm, 2),
                'base_km'       => round($baseKm, 1),
                'time_rate'     => round($timeRate, 2),
                'surge'         => round($surge, 2),
                'in_coverage'   => true,
                'zone'          => null,
            ];
        }

        $distance = self::distanceKm($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
        $pickupZone = self::matchingZone($pickupLat, $pickupLng);
        $deliveryZone = self::matchingZone($deliveryLat, $deliveryLng);
        $hasZones = Zone::active()->exists();
        $inCoverage = $hasZones ? (bool) $pickupZone && (bool) $deliveryZone : $distance <= $radius;

        $extraKm = max(0, $distance - $baseKm);
        $distanceFee = round($extraKm * $perKm, 2);

        $timeMin = max(1, ($distance / max($avgSpeed, 1)) * 60);
        $timeFee = round($timeMin * $timeRate, 2);

        $isShortDistance = ($distance < 1.0);
        if ($isShortDistance) {
            $subtotalFee = 4.00;
            $distanceFee = 0.00;
            $timeFee = 0.00;
        } else {
            $subtotalFee = $baseFare + $distanceFee + $timeFee;
        }

        $totalFee = self::roundFee($subtotalFee * $surge);

        return [
            'delivery_fee'     => $totalFee,
            'distance_km'      => round($distance, 2),
            'base_fare'        => $isShortDistance ? 4.00 : round($baseFare, 2),
            'distance_fee'     => $distanceFee,
            'time_fee'         => $timeFee,
            'time_min'         => round($timeMin, 1),
            'per_km'           => round($perKm, 2),
            'base_km'          => round($baseKm, 1),
            'time_rate'        => round($timeRate, 2),
            'avg_speed'        => round($avgSpeed, 1),
            'surge'            => round($surge, 2),
            'in_coverage'      => $inCoverage,
            'is_short_distance'=> $isShortDistance,
            'zone'             => $deliveryZone?->only(['id', 'name']),
        ];
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $apiKey = gs('google_maps_api');
        if ($apiKey) {
            $lat1_r = round($lat1, 4);
            $lng1_r = round($lng1, 4);
            $lat2_r = round($lat2, 4);
            $lng2_r = round($lng2, 4);
            $cacheKey = "gmaps_dist:{$lat1_r},{$lng1_r}:{$lat2_r},{$lng2_r}";

            $cachedDistance = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cachedDistance !== null) {
                return (float) $cachedDistance;
            }

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)->get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin'      => "{$lat1},{$lng1}",
                    'destination' => "{$lat2},{$lng2}",
                    'mode'        => 'driving',
                    'key'         => $apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['status'] === 'OK' && !empty($data['routes'])) {
                        $distanceMeters = $data['routes'][0]['legs'][0]['distance']['value'];
                        $distanceKm = $distanceMeters / 1000.0;
                        \Illuminate\Support\Facades\Cache::put($cacheKey, $distanceKm, 86400); // Cache for 24h
                        return $distanceKm;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to Haversine
            }
        }

        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function matchingZone(float $lat, float $lng): ?Zone
    {
        foreach (Zone::active()->get() as $zone) {
            if (insideZone(['lat' => $lat, 'long' => $lng], $zone)) {
                return $zone;
            }
        }

        return null;
    }

    public static function roundFee(float $amount): float
    {
        return round($amount);
    }

    private static function firstCoordinateValue(Request $request, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (!$request->has($key)) {
                continue;
            }

            $value = $request->input($key);
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
