<?php

namespace App\Services;

use App\Models\GeneralSetting;

class DriverFareEngine
{
    /**
     * Calculate driver earnings for an entire route/batch.
     *
     * @param float $distanceKm
     * @param float $durationMinutes
     * @param int $orderCount (1 = Single, 2 = Double, 3 = Triplet, 4 = Quadruple)
     * @param string $demandTier ('LOW', 'NORMAL', 'HIGH', 'VERY_HIGH')
     * @param float $tip
     * @return array
     */
    public static function calculateRouteFare(
        float $distanceKm,
        float $durationMinutes,
        int $orderCount = 1,
        string $demandTier = DemandEngine::TIER_NORMAL,
        float $tip = 0.0
    ): array {
        $rates = self::getRates();

        $baseFare       = $rates['base_fare'];
        $distRate       = $rates['rate_per_km'];
        $timeRate       = $rates['rate_per_minute'];
        $batchBonus     = self::getBatchBonus($orderCount, $rates);
        $batchType      = self::getBatchTypeName($orderCount);

        $distanceAmount = round($distanceKm * $distRate, 2);
        $timeAmount     = round($durationMinutes * $timeRate, 2);

        $subtotal = $baseFare + $distanceAmount + $timeAmount + $batchBonus;

        $multiplier = DemandEngine::getMultiplierForTier($demandTier);
        $incentive  = DemandEngine::getIncentiveForTier($demandTier);
        $basePoints = DemandEngine::getPointsForTier($demandTier);

        // Batch gamification points bonus: +10 pts for double, +20 for triplet, +35 for quad
        $batchPoints = match ($orderCount) {
            2 => 10,
            3 => 20,
            4 => 35,
            default => 0,
        };
        $totalPoints = $basePoints + $batchPoints;

        $driverEarning = round(($subtotal * $multiplier) + $incentive, 2);
        $safeTip = max(0.0, round($tip, 2));
        $totalPayout = round($driverEarning + $safeTip, 2);

        return [
            'base_fare'           => $baseFare,
            'distance_km'         => round($distanceKm, 2),
            'distance_rate'       => $distRate,
            'distance_amount'     => $distanceAmount,
            'time_minutes'        => round($durationMinutes, 1),
            'time_rate'           => $timeRate,
            'time_amount'         => $timeAmount,
            'batch_type'          => $batchType,
            'batch_bonus'         => $batchBonus,
            'demand_tier'         => $demandTier,
            'demand_multiplier'   => $multiplier,
            'demand_incentive'    => $incentive,
            'driver_earning'      => $driverEarning,
            'tip'                 => $safeTip,
            'total_payout'        => $totalPayout,
            'points'              => $totalPoints,
            'economic_policy'     => 'lizto_recharge_policy',
            'driver_retains_100'  => true,
        ];
    }

    /**
     * Calculate incremental earnings for adding a new order to an existing route.
     *
     * @param float $addedDistanceKm
     * @param float $addedDurationMinutes
     * @param int $newOrderCount (total count after adding this order)
     * @param string $demandTier
     * @param float $tip
     * @return array
     */
    public static function calculateIncrementalFare(
        float $addedDistanceKm,
        float $addedDurationMinutes,
        int $newOrderCount,
        string $demandTier = DemandEngine::TIER_NORMAL,
        float $tip = 0.0
    ): array {
        $rates = self::getRates();

        $distRate = $rates['rate_per_km'];
        $timeRate = $rates['rate_per_minute'];

        // Step bonus difference between new count and previous count
        $prevBonus = self::getBatchBonus($newOrderCount - 1, $rates);
        $currentBonus = self::getBatchBonus($newOrderCount, $rates);
        $stepBonus = max(0.0, round($currentBonus - $prevBonus, 2));

        $addedDistAmount = round($addedDistanceKm * $distRate, 2);
        $addedTimeAmount = round($addedDurationMinutes * $timeRate, 2);

        $subtotal = $addedDistAmount + $addedTimeAmount + $stepBonus;

        $multiplier = DemandEngine::getMultiplierForTier($demandTier);
        $incentive  = DemandEngine::getIncentiveForTier($demandTier);

        $incrementalEarning = round(($subtotal * $multiplier) + $incentive, 2);
        $safeTip = max(0.0, round($tip, 2));
        $totalPayout = round($incrementalEarning + $safeTip, 2);

        $points = DemandEngine::getPointsForTier($demandTier) + ($newOrderCount * 5);

        return [
            'added_distance_km'      => round($addedDistanceKm, 2),
            'distance_rate'          => $distRate,
            'distance_amount'        => $addedDistAmount,
            'added_duration_minutes' => round($addedDurationMinutes, 1),
            'time_rate'              => $timeRate,
            'time_amount'            => $addedTimeAmount,
            'batch_type'             => self::getBatchTypeName($newOrderCount),
            'batch_step_bonus'       => $stepBonus,
            'demand_tier'            => $demandTier,
            'demand_multiplier'      => $multiplier,
            'demand_incentive'       => $incentive,
            'driver_earning'         => $incrementalEarning,
            'tip'                    => $safeTip,
            'total_payout'           => $totalPayout,
            'points'                 => $points,
            'economic_policy'        => 'lizto_recharge_policy',
            'driver_retains_100'     => true,
        ];
    }

    /**
     * Get system fare and batch rate settings.
     */
    public static function getRates(): array
    {
        return [
            'base_fare'           => (float) (gs('driver_base_fare') ?? 3.00),
            'rate_per_km'         => (float) (gs('driver_rate_per_km') ?? 0.80),
            'rate_per_minute'     => (float) (gs('driver_rate_per_minute') ?? 0.10),
            'batch_double_bonus'  => (float) (gs('batch_double_bonus') ?? 1.50),
            'batch_triplet_bonus' => (float) (gs('batch_triplet_bonus') ?? 3.00),
            'batch_quad_bonus'    => (float) (gs('batch_quad_bonus') ?? 5.00),
        ];
    }

    public static function getBatchBonus(int $orderCount, ?array $rates = null): float
    {
        $rates = $rates ?? self::getRates();

        return match ($orderCount) {
            2 => $rates['batch_double_bonus'],
            3 => $rates['batch_triplet_bonus'],
            4 => $rates['batch_quad_bonus'],
            default => 0.0,
        };
    }

    public static function getBatchTypeName(int $orderCount): string
    {
        return match ($orderCount) {
            1 => 'SINGLE',
            2 => 'DOUBLE',
            3 => 'TRIPLET',
            default => 'QUADRUPLE',
        };
    }
}
