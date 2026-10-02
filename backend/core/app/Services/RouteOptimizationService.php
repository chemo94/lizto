<?php

namespace App\Services;

use App\Support\DeliveryPricing;

class RouteOptimizationService
{
    public const DEFAULT_AVG_SPEED_KMH = 25.0;
    public const STOP_SERVICE_TIME_MINUTES = 3.0;

    /**
     * Calculate direct distance between two points in km.
     */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return DeliveryPricing::distanceKm($lat1, $lng1, $lat2, $lng2);
    }

    /**
     * Estimate travel time in minutes for a given distance.
     */
    public static function estimateTimeMinutes(float $distanceKm, float $avgSpeedKmh = self::DEFAULT_AVG_SPEED_KMH): float
    {
        $speed = max(1.0, $avgSpeedKmh);
        return round(($distanceKm / $speed) * 60.0, 1);
    }

    /**
     * Optimize multi-stop route for 1 to 4 orders.
     *
     * @param array $origin ['lat' => float, 'lng' => float]
     * @param array $orders Array of normalized orders:
     *   [
     *     [
     *       'id'              => int,
     *       'type'            => 'delivery' | 'favor',
     *       'pickup_lat'      => float,
     *       'pickup_lng'      => float,
     *       'pickup_address'  => string,
     *       'pickup_name'     => string,
     *       'dropoff_lat'     => float,
     *       'dropoff_lng'     => float,
     *       'dropoff_address' => string,
     *       'dropoff_name'    => string,
     *     ],
     *     ...
     *   ]
     * @return array
     */
    public static function optimizeRoute(array $origin, array $orders): array
    {
        if (empty($orders)) {
            return [
                'ordered_stops'          => [],
                'total_distance_km'      => 0.0,
                'total_duration_minutes' => 0.0,
            ];
        }

        // Single order optimization is straightforward
        if (count($orders) === 1) {
            return self::buildSingleOrderRoute($origin, $orders[0]);
        }

        // Decompose orders into distinct pickup and dropoff stop definitions
        $stops = [];
        $orderCount = count($orders);

        for ($i = 0; $i < $orderCount; $i++) {
            $o = $orders[$i];
            $stops[] = [
                'stop_id'      => "P_{$i}",
                'order_index'  => $i,
                'order_id'     => $o['id'] ?? null,
                'order_type'   => $o['type'] ?? 'delivery',
                'type'         => 'pickup',
                'lat'          => (float) ($o['pickup_lat'] ?? $origin['lat']),
                'lng'          => (float) ($o['pickup_lng'] ?? $origin['lng']),
                'address'      => $o['pickup_address'] ?? '',
                'contact_name' => $o['pickup_name'] ?? 'Punto de recojo',
            ];
            $stops[] = [
                'stop_id'      => "D_{$i}",
                'order_index'  => $i,
                'order_id'     => $o['id'] ?? null,
                'order_type'   => $o['type'] ?? 'delivery',
                'type'         => 'dropoff',
                'lat'          => (float) ($o['dropoff_lat'] ?? $origin['lat']),
                'lng'          => (float) ($o['dropoff_lng'] ?? $origin['lng']),
                'address'      => $o['dropoff_address'] ?? '',
                'contact_name' => $o['dropoff_name'] ?? 'Cliente destino',
            ];
        }

        // Generate all valid permutations where pickup occurs before dropoff for every order
        $validSequences = [];
        self::generateValidSequences([], $stops, $orderCount, $validSequences);

        // Find sequence with minimal total travel distance
        $bestSequence = null;
        $minDistance = INF;

        foreach ($validSequences as $seq) {
            $dist = self::calculateSequenceDistance($origin, $seq);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $bestSequence = $seq;
            }
        }

        if (!$bestSequence) {
            $bestSequence = $stops;
        }

        return self::formatFinalRoute($origin, $bestSequence);
    }

    /**
     * Compute incremental route stats when adding a new order to an existing set of orders.
     */
    public static function calculateIncrementalRoute(array $existingOrders, array $newOrder, array $currentLocation): array
    {
        $baseRoute = self::optimizeRoute($currentLocation, $existingOrders);
        $bundledOrders = array_merge($existingOrders, [$newOrder]);
        $bundledRoute = self::optimizeRoute($currentLocation, $bundledOrders);

        $deltaDistance = max(0.0, round($bundledRoute['total_distance_km'] - $baseRoute['total_distance_km'], 2));
        $deltaTime = max(0.0, round($bundledRoute['total_duration_minutes'] - $baseRoute['total_duration_minutes'], 1));

        return [
            'base_route'             => $baseRoute,
            'bundled_route'          => $bundledRoute,
            'added_distance_km'      => $deltaDistance,
            'added_duration_minutes' => $deltaTime,
        ];
    }

    /**
     * Recursive generator for valid stop permutations satisfying pickup-before-dropoff constraint.
     */
    private static function generateValidSequences(array $current, array $remaining, int $orderCount, array &$results): void
    {
        if (empty($remaining)) {
            $results[] = $current;
            return;
        }

        // Count how many pickups have been visited for each order_index
        $pickedUp = [];
        foreach ($current as $stop) {
            if ($stop['type'] === 'pickup') {
                $pickedUp[$stop['order_index']] = true;
            }
        }

        for ($i = 0; $i < count($remaining); $i++) {
            $candidate = $remaining[$i];

            // If candidate is a dropoff, its corresponding pickup MUST already be in $current
            if ($candidate['type'] === 'dropoff' && empty($pickedUp[$candidate['order_index']])) {
                continue;
            }

            $nextCurrent = $current;
            $nextCurrent[] = $candidate;

            $nextRemaining = $remaining;
            array_splice($nextRemaining, $i, 1);

            self::generateValidSequences($nextCurrent, $nextRemaining, $orderCount, $results);
        }
    }

    private static function calculateSequenceDistance(array $origin, array $stops): float
    {
        $totalDist = 0.0;
        $prevLat = $origin['lat'];
        $prevLng = $origin['lng'];

        foreach ($stops as $stop) {
            $totalDist += self::distance($prevLat, $prevLng, $stop['lat'], $stop['lng']);
            $prevLat = $stop['lat'];
            $prevLng = $stop['lng'];
        }

        return $totalDist;
    }

    private static function buildSingleOrderRoute(array $origin, array $order): array
    {
        $pLat = (float) ($order['pickup_lat'] ?? $origin['lat']);
        $pLng = (float) ($order['pickup_lng'] ?? $origin['lng']);
        $dLat = (float) ($order['dropoff_lat'] ?? $origin['lat']);
        $dLng = (float) ($order['dropoff_lng'] ?? $origin['lng']);

        $leg1Dist = round(self::distance($origin['lat'], $origin['lng'], $pLat, $pLng), 2);
        $leg1Time = self::estimateTimeMinutes($leg1Dist) + self::STOP_SERVICE_TIME_MINUTES;

        $leg2Dist = round(self::distance($pLat, $pLng, $dLat, $dLng), 2);
        $leg2Time = self::estimateTimeMinutes($leg2Dist) + self::STOP_SERVICE_TIME_MINUTES;

        $totalDist = round($leg1Dist + $leg2Dist, 2);
        $totalTime = round($leg1Time + $leg2Time, 1);

        $stops = [
            [
                'stop_number'             => 1,
                'type'                    => 'pickup',
                'order_id'                => $order['id'] ?? null,
                'order_type'              => $order['type'] ?? 'delivery',
                'lat'                     => $pLat,
                'lng'                     => $pLng,
                'address'                 => $order['pickup_address'] ?? '',
                'contact_name'            => $order['pickup_name'] ?? 'Punto de recojo',
                'leg_distance_km'         => $leg1Dist,
                'leg_time_minutes'        => $leg1Time,
                'cumulative_distance_km'  => $leg1Dist,
                'cumulative_time_minutes' => $leg1Time,
            ],
            [
                'stop_number'             => 2,
                'type'                    => 'dropoff',
                'order_id'                => $order['id'] ?? null,
                'order_type'              => $order['type'] ?? 'delivery',
                'lat'                     => $dLat,
                'lng'                     => $dLng,
                'address'                 => $order['dropoff_address'] ?? '',
                'contact_name'            => $order['dropoff_name'] ?? 'Cliente destino',
                'leg_distance_km'         => $leg2Dist,
                'leg_time_minutes'        => $leg2Time,
                'cumulative_distance_km'  => $totalDist,
                'cumulative_time_minutes' => $totalTime,
            ],
        ];

        return [
            'ordered_stops'          => $stops,
            'total_distance_km'      => $totalDist,
            'total_duration_minutes' => $totalTime,
        ];
    }

    private static function formatFinalRoute(array $origin, array $sequence): array
    {
        $stops = [];
        $cumDist = 0.0;
        $cumTime = 0.0;
        $prevLat = $origin['lat'];
        $prevLng = $origin['lng'];

        foreach ($sequence as $idx => $s) {
            $legDist = round(self::distance($prevLat, $prevLng, $s['lat'], $s['lng']), 2);
            $legTime = round(self::estimateTimeMinutes($legDist) + self::STOP_SERVICE_TIME_MINUTES, 1);

            $cumDist = round($cumDist + $legDist, 2);
            $cumTime = round($cumTime + $legTime, 1);

            $stops[] = [
                'stop_number'             => $idx + 1,
                'type'                    => $s['type'],
                'order_id'                => $s['order_id'],
                'order_type'              => $s['order_type'],
                'lat'                     => $s['lat'],
                'lng'                     => $s['lng'],
                'address'                 => $s['address'],
                'contact_name'            => $s['contact_name'],
                'leg_distance_km'         => $legDist,
                'leg_time_minutes'        => $legTime,
                'cumulative_distance_km'  => $cumDist,
                'cumulative_time_minutes' => $cumTime,
            ];

            $prevLat = $s['lat'];
            $prevLng = $s['lng'];
        }

        return [
            'ordered_stops'          => $stops,
            'total_distance_km'      => $cumDist,
            'total_duration_minutes' => $cumTime,
        ];
    }
}
