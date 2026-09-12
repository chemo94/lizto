<?php

namespace App\Services\H3;

use App\Constants\Status;
use App\Models\Driver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Uber H3 + Redis Real-Time Driver & Courier Geolocation Service.
 * 
 * Provides sub-millisecond hexagonal spatial queries using Uber H3 rings
 * and Redis sets/hashes, with 100% graceful fallback to MySQL.
 */
class DriverGeoRedisService
{
    const DRIVER_POS_PREFIX  = 'driver:pos:';
    const DRIVER_CELL_PREFIX = 'driver:cell:';
    const GEO_H3_PREFIX      = 'geo:h3:';
    const GEO_DRIVERS_SET    = 'geo:drivers';
    const TTL_SECONDS        = 300; // 5 minutes inactivity TTL

    protected static ?bool $redisAvailable = null;
    protected static int $lastConnectionAttempt = 0;
    const RECONNECT_COOLDOWN_SECONDS = 30;

    /**
     * Test and retrieve Redis connection safely with timeout detection.
     */
    public static function getRedis()
    {
        $now = time();

        // If Redis failed recently, don't stall HTTP workers with repeated connection timeouts
        if (self::$redisAvailable === false && ($now - self::$lastConnectionAttempt) < self::RECONNECT_COOLDOWN_SECONDS) {
            return null;
        }

        try {
            self::$lastConnectionAttempt = $now;

            // Ensure predis autoloader is loaded if needed
            if (!class_exists(\Predis\Client::class) && file_exists(base_path('vendor/predis/predis/autoload.php'))) {
                require_once base_path('vendor/predis/predis/autoload.php');
            }

            $redis = Redis::connection();
            // Quick ping with low timeout
            $redis->ping();
            self::$redisAvailable = true;
            return $redis;
        } catch (\Throwable $e) {
            self::$redisAvailable = false;
            Log::debug('Redis is not reachable, falling back to database: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a driver or courier's real-time position in Redis with H3 indexing.
     *
     * @param Driver|int $driver
     * @param float $lat
     * @param float $lng
     * @param float|null $bearing
     * @param float|null $speed
     * @param string|null $serviceType 'ride', 'delivery', 'both'
     * @param int|null $serviceId Service category ID (e.g. mototaxi, auto economico, uberx)
     * @param bool $isOnline
     * @return array|null Returns ['h3' => ..., 'h3_res9' => ...] or null on failure
     */
    public static function updateDriverPosition(
        Driver|int $driver,
        float $lat,
        float $lng,
        ?float $bearing = null,
        ?float $speed = null,
        ?string $serviceType = null,
        ?int $serviceId = null,
        bool $isOnline = true
    ): ?array {
        $driverId = $driver instanceof Driver ? $driver->id : (int) $driver;

        if ($driver instanceof Driver) {
            $serviceType = $serviceType ?? $driver->service_type;
            $serviceId   = $serviceId ?? $driver->service_id;
        }

        // Calculate Uber H3 indices at standard resolutions
        $h3Res8 = H3Grid::geoToH3($lat, $lng, 8); // ~460m edge
        $h3Res9 = H3Grid::geoToH3($lat, $lng, 9); // ~174m edge

        $redis = self::getRedis();
        if (!$redis) {
            return [
                'h3'      => $h3Res8,
                'h3_res9' => $h3Res9,
            ];
        }

        try {
            $cellKey = self::DRIVER_CELL_PREFIX . $driverId;
            $oldCell = $redis->get($cellKey);

            // If driver moved to a new H3 cell, remove from old cell set
            if ($oldCell && $oldCell !== $h3Res8) {
                $redis->srem(self::GEO_H3_PREFIX . $oldCell, $driverId);
            }

            // Save new H3 cell reference
            $redis->setex($cellKey, self::TTL_SECONDS, $h3Res8);

            // Add driver ID to new H3 cell set
            $redis->sadd(self::GEO_H3_PREFIX . $h3Res8, $driverId);
            $redis->expire(self::GEO_H3_PREFIX . $h3Res8, self::TTL_SECONDS);

            // Also index in fine-grained Res 9 cell for hyper-local micro dispatch
            $redis->sadd(self::GEO_H3_PREFIX . $h3Res9, $driverId);
            $redis->expire(self::GEO_H3_PREFIX . $h3Res9, self::TTL_SECONDS);

            // Save driver position hash
            $posKey = self::DRIVER_POS_PREFIX . $driverId;
            $redis->hmset($posKey, [
                'id'           => $driverId,
                'lat'          => $lat,
                'lng'          => $lng,
                'bearing'      => $bearing ?? 0,
                'speed'        => $speed ?? 0,
                'service_type' => $serviceType ?? '',
                'service_id'   => $serviceId ?? 0,
                'h3'           => $h3Res8,
                'h3_res9'      => $h3Res9,
                'updated_at'   => now()->timestamp,
                'is_online'    => $isOnline ? 1 : 0,
            ]);
            $redis->expire($posKey, self::TTL_SECONDS);

            // Maintain standard geospatial sorted set
            $redis->geoadd(self::GEO_DRIVERS_SET, $lng, $lat, (string) $driverId);

            return [
                'h3'      => $h3Res8,
                'h3_res9' => $h3Res9,
            ];
        } catch (\Throwable $e) {
            Log::warning("Failed to update driver $driverId in Redis H3: " . $e->getMessage());
            return [
                'h3'      => $h3Res8,
                'h3_res9' => $h3Res9,
            ];
        }
    }

    /**
     * Remove driver from Redis H3 and spatial indices when logging out or going offline.
     */
    public static function removeDriver(int $driverId): bool
    {
        $redis = self::getRedis();
        if (!$redis) {
            return false;
        }

        try {
            $cellKey = self::DRIVER_CELL_PREFIX . $driverId;
            $oldCell = $redis->get($cellKey);

            if ($oldCell) {
                $redis->srem(self::GEO_H3_PREFIX . $oldCell, $driverId);
            }

            $redis->del($cellKey);
            $redis->del(self::DRIVER_POS_PREFIX . $driverId);
            $redis->zrem(self::GEO_DRIVERS_SET, (string) $driverId);

            return true;
        } catch (\Throwable $e) {
            Log::warning("Failed to remove driver $driverId from Redis H3: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find nearby online drivers within a given radius using H3 hexagonal k-rings.
     *
     * @param float $lat Search latitude
     * @param float $lng Search longitude
     * @param float $radiusKm Maximum radius in km (default 3.0 km)
     * @param int|null $serviceId Specific service ID filter (e.g. mototaxi)
     * @param string|array|null $serviceType Filter by service_type ('ride', 'delivery', 'both')
     * @param int $limit Maximum number of drivers to return
     * @param bool $fallbackToDb Whether to fallback to database query if Redis returns 0
     * @return Collection Collection of driver data arrays sorted by distance ascending
     */
    public static function findNearby(
        float $lat,
        float $lng,
        float $radiusKm = 3.0,
        ?int $serviceId = null,
        string|array|null $serviceType = null,
        int $limit = 20,
        bool $fallbackToDb = true
    ): Collection {
        $redis = self::getRedis();

        if ($redis) {
            try {
                // 1. Calculate origin H3 cell at resolution 8
                $originH3 = H3Grid::geoToH3($lat, $lng, 8);

                // 2. Determine required k-ring expansion
                $k = H3Grid::kForRadius($radiusKm, 8);
                $cells = H3Grid::kRing($originH3, $k);

                // 3. Collect all candidate driver IDs from the H3 cells
                $driverIds = [];
                foreach ($cells as $cell) {
                    $cellMembers = $redis->smembers(self::GEO_H3_PREFIX . $cell);
                    if (!empty($cellMembers)) {
                        foreach ($cellMembers as $m) {
                            $driverIds[(int) $m] = true;
                        }
                    }
                }

                if (!empty($driverIds)) {
                    $candidateIds = array_keys($driverIds);
                    $nowTs = now()->timestamp;
                    $matchedDrivers = [];

                    // 4. Fetch details for candidate drivers
                    foreach ($candidateIds as $dId) {
                        $pos = $redis->hgetall(self::DRIVER_POS_PREFIX . $dId);
                        if (empty($pos) || !isset($pos['lat'], $pos['lng'])) {
                            continue;
                        }

                        // Check freshness (within 5 minutes) and online status
                        if (($nowTs - (int) ($pos['updated_at'] ?? 0)) > self::TTL_SECONDS) {
                            continue;
                        }
                        if (isset($pos['is_online']) && (int) $pos['is_online'] !== 1) {
                            continue;
                        }

                        // Filter service_id
                        if ($serviceId !== null && (int) ($pos['service_id'] ?? 0) !== (int) $serviceId) {
                            continue;
                        }

                        // Filter service_type
                        if ($serviceType !== null) {
                            $driverType = $pos['service_type'] ?? '';
                            if (is_array($serviceType)) {
                                if (!in_array($driverType, $serviceType)) {
                                    continue;
                                }
                            } elseif ($driverType !== $serviceType && $driverType !== 'both') {
                                continue;
                            }
                        }

                        // Compute exact geodesic distance
                        $distKm = H3Grid::distanceKm($lat, $lng, (float) $pos['lat'], (float) $pos['lng']);
                        if ($distKm <= $radiusKm) {
                            $matchedDrivers[] = [
                                'id'           => (int) $dId,
                                'latitude'     => (float) $pos['lat'],
                                'longitude'    => (float) $pos['lng'],
                                'bearing'      => (float) ($pos['bearing'] ?? 0),
                                'speed'        => (float) ($pos['speed'] ?? 0),
                                'service_id'   => (int) ($pos['service_id'] ?? 0),
                                'service_type' => $pos['service_type'] ?? '',
                                'h3'           => $pos['h3'] ?? '',
                                'distance_km'  => round($distKm, 2),
                                'from_cache'   => true,
                            ];
                        }
                    }

                    if (!empty($matchedDrivers)) {
                        // Sort by distance ascending
                        usort($matchedDrivers, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);
                        $sliced = array_slice($matchedDrivers, 0, $limit);

                        // Enrich with driver model details (firstname, lastname, image, service)
                        return self::enrichDriverDetails($sliced);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Redis H3 findNearby query exception: ' . $e->getMessage());
            }
        }

        // Fallback to database query if Redis has no data or is offline
        if ($fallbackToDb) {
            return self::findNearbyFromDb($lat, $lng, $radiusKm, $serviceId, $serviceType, $limit);
        }

        return collect();
    }

    /**
     * Dedicated method for courier dispatch (favors & delivery orders).
     * Filters for active couriers with delivery permissions and positive wallet.
     */
    public static function findNearbyCouriers(
        float $lat,
        float $lng,
        float $radiusKm = 5.0,
        array $excludeDriverIds = [],
        int $limit = 10
    ): Collection {
        $couriers = self::findNearby(
            $lat,
            $lng,
            $radiusKm,
            serviceId: null,
            serviceType: ['delivery', 'both'],
            limit: $limit * 2,
            fallbackToDb: true
        );

        if (!empty($excludeDriverIds)) {
            $excludeFlip = array_flip($excludeDriverIds);
            $couriers = $couriers->reject(fn ($c) => isset($excludeFlip[$c['id']]));
        }

        return $couriers->slice(0, $limit)->values();
    }

    /**
     * Database fallback query using Haversine formula on active drivers.
     */
    public static function findNearbyFromDb(
        float $lat,
        float $lng,
        float $radiusKm,
        ?int $serviceId = null,
        string|array|null $serviceType = null,
        int $limit = 20
    ): Collection {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(0.0001, cos(deg2rad($lat))));
        $minLat   = $lat - $latDelta;
        $maxLat   = $lat + $latDelta;
        $minLng   = $lng - $lngDelta;
        $maxLng   = $lng + $lngDelta;

        $query = Driver::active()
            ->where('online_status', Status::YES)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lot')
            ->whereBetween('current_lat', [$minLat, $maxLat])
            ->whereBetween('current_lot', [$minLng, $maxLng]);

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        }

        if ($serviceType !== null) {
            if (is_array($serviceType)) {
                $query->whereIn('service_type', $serviceType);
            } else {
                $query->where(function ($q) use ($serviceType) {
                    $q->where('service_type', $serviceType)
                      ->orWhere('service_type', 'both');
                });
            }
        }

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lot) - radians(?)) + sin(radians(?)) * sin(radians(current_lat))))";

        $drivers = $query
            ->selectRaw("*, $haversine AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->with('service')
            ->get();

        return $drivers->map(function ($d) use ($lat, $lng) {
            $h3 = H3Grid::geoToH3((float) $d->current_lat, (float) $d->current_lot, 8);
            return [
                'id'           => $d->id,
                'firstname'    => $d->firstname,
                'lastname'     => $d->lastname,
                'latitude'     => (float) $d->current_lat,
                'longitude'    => (float) $d->current_lot,
                'bearing'      => (float) ($d->bearing ?? 0),
                'speed'        => 0.0,
                'service_id'   => $d->service_id,
                'service_name' => $d->service?->name,
                'service_type' => $d->service_type,
                'distance_km'  => round($d->distance, 2),
                'image'        => $d->image,
                'h3'           => $h3,
                'from_cache'   => false,
            ];
        });
    }

    /**
     * Enrich driver lightweight records with database model attributes.
     */
    protected static function enrichDriverDetails(array $driverRows): Collection
    {
        if (empty($driverRows)) {
            return collect();
        }

        $ids = array_column($driverRows, 'id');
        $driversDb = Driver::whereIn('id', $ids)
            ->with('service')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($driverRows as $row) {
            $d = $driversDb->get($row['id']);
            if (!$d) {
                continue;
            }

            $result[] = [
                'id'           => $d->id,
                'firstname'    => $d->firstname,
                'lastname'     => $d->lastname,
                'latitude'     => $row['latitude'],
                'longitude'    => $row['longitude'],
                'bearing'      => $row['bearing'] ?? (float) ($d->bearing ?? 0),
                'speed'        => $row['speed'] ?? 0.0,
                'service_id'   => $d->service_id,
                'service_name' => $d->service?->name,
                'service_type' => $d->service_type,
                'distance_km'  => $row['distance_km'],
                'image'        => $d->image,
                'h3'           => $row['h3'] ?? '',
                'from_cache'   => true,
            ];
        }

        return collect($result);
    }

    /**
     * Warm up Redis from MySQL for all online drivers.
     */
    public static function warmUpOnlineDrivers(): int
    {
        $drivers = Driver::active()
            ->where('online_status', Status::YES)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lot')
            ->get();

        $count = 0;
        foreach ($drivers as $driver) {
            self::updateDriverPosition(
                $driver,
                (float) $driver->current_lat,
                (float) $driver->current_lot,
                bearing: (float) ($driver->bearing ?? 0),
                speed: 0.0,
                serviceType: $driver->service_type,
                serviceId: $driver->service_id,
                isOnline: true
            );
            $count++;
        }

        return $count;
    }
}
