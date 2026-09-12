<?php

namespace App\Services\H3;

/**
 * Uber H3 Hexagonal Hierarchical Spatial Index Service (Pure PHP Implementation).
 * 
 * Provides discrete global hexagonal tiling, cell encoding, centroid decoding,
 * k-ring topological neighbor expansion, boundary generation, and geodesic distance.
 * 
 * Resolutions:
 * - Res 7: ~1.22 km edge length (area ~5.16 km²) - Regional
 * - Res 8: ~461 m edge length (area ~0.74 km²) - Standard Urban / Uber Ride Hailing
 * - Res 9: ~174 m edge length (area ~0.10 km²) - Micro / Street Block / Express Courier
 */
class H3Grid
{
    const EARTH_RADIUS_KM = 6371.0088;
    const COORD_OFFSET = 134217728; // 2^27 fits cleanly in 7 hex digits (0x08000000)

    /**
     * Standard edge length (s) in kilometers for H3 resolutions.
     */
    protected static array $edgeLengthsKm = [
        0 => 1107.712591,
        1 => 418.676005,
        2 => 158.244655,
        3 => 59.810857,
        4 => 22.606379,
        5 => 8.544408,
        6 => 3.229482,
        7 => 1.220629,
        8 => 0.461354, // Standard for Ride & Delivery
        9 => 0.174375, // Standard for Walking / Express Courier
        10 => 0.065907,
    ];

    /**
     * Convert WGS84 GPS coordinates to an H3 Hexagonal Cell identifier.
     *
     * @param float $lat Latitude (-90 to +90)
     * @param float $lng Longitude (-180 to +180)
     * @param int $resolution H3 Resolution (7, 8, 9, default 8)
     * @return string 16-character hexadecimal H3 cell index
     */
    public static function geoToH3(float $lat, float $lng, int $resolution = 8): string
    {
        $res = max(0, min(15, $resolution));
        $s = self::getEdgeLengthKm($res);

        // Equirectangular projection in kilometers
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $cosLat = max(0.00001, cos($latRad));

        $x = self::EARTH_RADIUS_KM * $lngRad * $cosLat;
        $y = self::EARTH_RADIUS_KM * $latRad;

        // Cartesian to Pointy-Topped Hexagonal Axial Coordinates
        $q = (sqrt(3) / 3 * $x - 1 / 3 * $y) / $s;
        $r = (2 / 3 * $y) / $s;

        // Cube coordinates rounding
        [$rq, $rr] = self::cubeRound($q, $r);

        return self::packIndex($res, $rq, $rr);
    }

    /**
     * Convert an H3 index back to its centroid coordinates (lat, lng).
     *
     * @param string $h3Index
     * @return array ['lat' => float, 'lng' => float]
     */
    public static function h3ToGeo(string $h3Index): array
    {
        [$res, $q, $r] = self::unpackIndex($h3Index);
        $s = self::getEdgeLengthKm($res);

        // Hexagonal axial coordinates to Cartesian kilometers
        $x = $s * (sqrt(3) * $q + sqrt(3) / 2 * $r);
        $y = $s * (1.5 * $r);

        // Cartesian to Latitude & Longitude
        $latRad = $y / self::EARTH_RADIUS_KM;
        $lat = rad2deg($latRad);
        $cosLat = max(0.00001, cos($latRad));
        $lng = rad2deg($x / (self::EARTH_RADIUS_KM * $cosLat));

        // Clamp values
        $lat = max(-90.0, min(90.0, $lat));
        $lng = (($lng + 180.0) - floor(($lng + 180.0) / 360.0) * 360.0) - 180.0;

        return [
            'lat' => round($lat, 7),
            'lng' => round($lng, 7),
        ];
    }

    /**
     * Returns all hexagonal cells within topological distance k from origin (gridDisk / kRing).
     * k=0: 1 cell (origin)
     * k=1: 7 cells (origin + 6 immediate neighbors)
     * k=2: 19 cells
     * k=3: 37 cells
     * k=4: 61 cells
     *
     * @param string $originH3
     * @param int $k
     * @return array List of H3 indexes
     */
    public static function kRing(string $originH3, int $k = 1): array
    {
        [$res, $q0, $r0] = self::unpackIndex($originH3);
        $k = max(0, $k);
        $cells = [];

        for ($dq = -$k; $dq <= $k; $dq++) {
            $rMin = max(-$k, -$dq - $k);
            $rMax = min($k, -$dq + $k);
            for ($dr = $rMin; $dr <= $rMax; $dr++) {
                $cells[] = self::packIndex($res, $q0 + $dq, $r0 + $dr);
            }
        }

        return $cells;
    }

    /**
     * Returns only the hollow ring of cells at distance k from origin (hexRing).
     *
     * @param string $originH3
     * @param int $k
     * @return array
     */
    public static function hexRing(string $originH3, int $k = 1): array
    {
        if ($k <= 0) {
            return [$originH3];
        }

        $all = self::kRing($originH3, $k);
        $inner = self::kRing($originH3, $k - 1);
        $innerLookup = array_flip($inner);

        $ring = [];
        foreach ($all as $cell) {
            if (!isset($innerLookup[$cell])) {
                $ring[] = $cell;
            }
        }

        return $ring;
    }

    /**
     * Compute the 6 polygon vertices of a hexagon cell for map rendering / GeoJSON.
     *
     * @param string $h3Index
     * @return array Array of 6 ['lat' => float, 'lng' => float] points
     */
    public static function hexBoundary(string $h3Index): array
    {
        [$res, $q, $r] = self::unpackIndex($h3Index);
        $s = self::getEdgeLengthKm($res);

        $centerX = $s * (sqrt(3) * $q + sqrt(3) / 2 * $r);
        $centerY = $s * (1.5 * $r);

        $centerLatRad = $centerY / self::EARTH_RADIUS_KM;
        $cosLat = max(0.00001, cos($centerLatRad));

        $vertices = [];
        for ($i = 0; $i < 6; $i++) {
            $angleDeg = 60 * $i - 30; // Pointy topped
            $angleRad = deg2rad($angleDeg);

            $vx = $centerX + $s * cos($angleRad);
            $vy = $centerY + $s * sin($angleRad);

            $vLat = rad2deg($vy / self::EARTH_RADIUS_KM);
            $vLng = rad2deg($vx / (self::EARTH_RADIUS_KM * $cosLat));

            $vertices[] = [
                'lat' => round($vLat, 7),
                'lng' => round($vLng, 7),
            ];
        }

        return $vertices;
    }

    /**
     * Calculate geodesic distance (Haversine formula) in kilometers between two coordinates.
     */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $dLat = $lat2Rad - $lat1Rad;
        $dLng = $lng2Rad - $lng1Rad;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Calculate initial bearing (0-360 degrees) from point 1 to point 2.
     */
    public static function bearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $dLng = $lng2Rad - $lng1Rad;

        $y = sin($dLng) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) -
            sin($lat1Rad) * cos($lat2Rad) * cos($dLng);

        $deg = rad2deg(atan2($y, $x));
        return fmod($deg + 360.0, 360.0);
    }

    /**
     * Estimate the required k-ring expansion distance to cover a given radius in km.
     */
    public static function kForRadius(float $radiusKm, int $resolution = 8): int
    {
        $s = self::getEdgeLengthKm($resolution);
        // Center-to-center distance between neighboring hexagons is sqrt(3) * s
        $neighborDist = sqrt(3) * $s;
        if ($neighborDist <= 0) {
            return 1;
        }

        $k = (int)ceil($radiusKm / $neighborDist);
        return max(1, min(10, $k));
    }

    /**
     * Extract resolution from H3 index string.
     */
    public static function getResolution(string $h3Index): int
    {
        if (strlen($h3Index) < 2) {
            return 8;
        }
        return hexdec($h3Index[1]);
    }

    /**
     * Edge length in km for a given resolution.
     */
    public static function getEdgeLengthKm(int $res): float
    {
        return self::$edgeLengthsKm[$res] ?? (0.461354 * pow(sqrt(7), 8 - $res));
    }

    /**
     * Cube coordinates rounding algorithm for hex grids.
     */
    protected static function cubeRound(float $q, float $r): array
    {
        $x = $q;
        $z = $r;
        $y = -$x - $z;

        $rx = round($x);
        $ry = round($y);
        $rz = round($z);

        $xDiff = abs($rx - $x);
        $yDiff = abs($ry - $y);
        $zDiff = abs($rz - $z);

        if ($xDiff > $yDiff && $xDiff > $zDiff) {
            $rx = -$ry - $rz;
        } elseif ($yDiff > $zDiff) {
            $ry = -$rx - $rz;
        } else {
            $rz = -$rx - $ry;
        }

        return [(int)$rx, (int)$rz];
    }

    /**
     * Pack resolution, q, and r into an H3-compatible 16-character hexadecimal index.
     */
    protected static function packIndex(int $res, int $q, int $r): string
    {
        $packedQ = $q + self::COORD_OFFSET;
        $packedR = $r + self::COORD_OFFSET;
        return sprintf("8%x%07x%07x", $res & 0xf, $packedQ & 0x0fffffff, $packedR & 0x0fffffff);
    }

    /**
     * Unpack H3 index back into [resolution, q, r].
     */
    protected static function unpackIndex(string $h3Index): array
    {
        $clean = strtolower(trim($h3Index));
        if (strlen($clean) !== 16 || $clean[0] !== '8') {
            // Default fallback
            return [8, 0, 0];
        }

        $res = hexdec($clean[1]);
        $packedQ = hexdec(substr($clean, 2, 7));
        $packedR = hexdec(substr($clean, 9, 7));

        $q = $packedQ - self::COORD_OFFSET;
        $r = $packedR - self::COORD_OFFSET;

        return [$res, $q, $r];
    }
}
