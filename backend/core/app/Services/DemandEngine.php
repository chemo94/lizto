<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Favor;
use App\Support\DeliveryPricing;

class DemandEngine
{
    public const TIER_LOW       = 'LOW';
    public const TIER_NORMAL    = 'NORMAL';
    public const TIER_HIGH      = 'HIGH';
    public const TIER_VERY_HIGH = 'VERY_HIGH';

    /**
     * Compute real-time supply and demand metrics in a given geographical area.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $radiusKm
     * @return array
     */
    public static function calculateDemandTier(float $latitude, float $longitude, float $radiusKm = 5.0): array
    {
        // 1. Count pending delivery orders in area
        $pendingDeliveryOrders = DeliveryOrder::whereNull('driver_id')
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->get();

        $deliveryCount = 0;
        foreach ($pendingDeliveryOrders as $order) {
            $pLat = $order->delivery_lat ?? ($order->store ? (float) $order->store->latitude : null);
            $pLng = $order->delivery_lng ?? ($order->store ? (float) $order->store->longitude : null);
            if ($pLat && $pLng) {
                if (DeliveryPricing::distanceKm($latitude, $longitude, (float) $pLat, (float) $pLng) <= $radiusKm) {
                    $deliveryCount++;
                }
            } else {
                $deliveryCount++;
            }
        }

        // 2. Count pending favor orders in area
        $pendingFavors = Favor::where('status', 'searching_courier')
            ->where(function ($q) {
                $q->whereNull('courier_id')
                  ->orWhere('dispatch_mode', 'seller_broadcast');
            })
            ->get();

        $favorCount = 0;
        foreach ($pendingFavors as $favor) {
            $fLat = $favor->pickup_lat ?? $favor->delivery_lat;
            $fLng = $favor->pickup_lng ?? $favor->delivery_lng;
            if ($fLat && $fLng) {
                if (DeliveryPricing::distanceKm($latitude, $longitude, (float) $fLat, (float) $fLng) <= $radiusKm) {
                    $favorCount++;
                }
            } else {
                $favorCount++;
            }
        }

        $totalPendingOrders = $deliveryCount + $favorCount;

        // 3. Count active, economically eligible drivers nearby
        $onlineDrivers = Driver::where('is_online', 1)
            ->where('status', 1)
            ->get();

        $availableDriversCount = 0;
        foreach ($onlineDrivers as $driver) {
            // Check Lizto economic policy
            $economicCheck = DriverEconomicPolicyService::canDriverReceiveOrders($driver);
            if (!$economicCheck['allowed']) {
                continue; // Cannot receive orders, not counted in active supply
            }

            if ($driver->latitude && $driver->longitude) {
                $dist = DeliveryPricing::distanceKm($latitude, $longitude, (float) $driver->latitude, (float) $driver->longitude);
                if ($dist <= $radiusKm) {
                    $availableDriversCount++;
                }
            } else {
                // If coordinates not reported yet, count if recently active
                $availableDriversCount++;
            }
        }

        $ratio = $totalPendingOrders / max(1, $availableDriversCount);

        // 4. Assign tier and multipliers based on ratio
        if ($ratio < 0.8) {
            $tier = self::TIER_LOW;
            $multiplier = 1.00;
            $incentiveAmount = 0.00;
            $points = 5;
        } elseif ($ratio < 1.5) {
            $tier = self::TIER_NORMAL;
            $multiplier = 1.00;
            $incentiveAmount = 0.00;
            $points = 10;
        } elseif ($ratio < 2.5) {
            $tier = self::TIER_HIGH;
            $multiplier = 1.10;
            $incentiveAmount = 1.00;
            $points = 20;
        } else {
            $tier = self::TIER_VERY_HIGH;
            $multiplier = 1.25;
            $incentiveAmount = 2.50;
            $points = 35;
        }

        return [
            'tier'              => $tier,
            'multiplier'        => $multiplier,
            'incentive_amount'  => $incentiveAmount,
            'points'            => $points,
            'demand_ratio'      => round($ratio, 2),
            'pending_orders'    => $totalPendingOrders,
            'available_drivers' => $availableDriversCount,
            'radius_km'         => $radiusKm,
        ];
    }

    public static function getMultiplierForTier(string $tier): float
    {
        return match ($tier) {
            self::TIER_VERY_HIGH => 1.25,
            self::TIER_HIGH      => 1.10,
            default              => 1.00,
        };
    }

    public static function getIncentiveForTier(string $tier): float
    {
        return match ($tier) {
            self::TIER_VERY_HIGH => 2.50,
            self::TIER_HIGH      => 1.00,
            default              => 0.00,
        };
    }

    public static function getPointsForTier(string $tier): int
    {
        return match ($tier) {
            self::TIER_VERY_HIGH => 35,
            self::TIER_HIGH      => 20,
            self::TIER_LOW       => 5,
            default              => 10,
        };
    }
}
