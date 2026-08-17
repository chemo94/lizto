<?php
function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($dLng / 2) * sin($dLng / 2);

    return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
}

echo "Short distance (Tarapoto internal): " . distanceKm(-6.485, -76.360, -6.495, -76.350) . " km\n";
echo "Long distance (Tarapoto to Lima): " . distanceKm(-6.485, -76.360, -12.046, -77.042) . " km\n";
