<?php

namespace Tests\Unit;

use App\Services\DemandEngine;
use App\Services\DriverFareEngine;
use App\Services\RouteOptimizationService;
use App\Services\BatchingEngine;
use PHPUnit\Framework\TestCase;

class PhaseTwoEnginesTest extends TestCase
{
    /**
     * Test DemandEngine tier multipliers, incentives, and points.
     */
    public function test_demand_engine_tiers_and_incentives(): void
    {
        $this->assertEquals(1.00, DemandEngine::getMultiplierForTier(DemandEngine::TIER_LOW));
        $this->assertEquals(0.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_LOW));
        $this->assertEquals(5, DemandEngine::getPointsForTier(DemandEngine::TIER_LOW));

        $this->assertEquals(1.00, DemandEngine::getMultiplierForTier(DemandEngine::TIER_NORMAL));
        $this->assertEquals(0.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_NORMAL));
        $this->assertEquals(10, DemandEngine::getPointsForTier(DemandEngine::TIER_NORMAL));

        $this->assertEquals(1.10, DemandEngine::getMultiplierForTier(DemandEngine::TIER_HIGH));
        $this->assertEquals(1.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_HIGH));
        $this->assertEquals(20, DemandEngine::getPointsForTier(DemandEngine::TIER_HIGH));

        $this->assertEquals(1.25, DemandEngine::getMultiplierForTier(DemandEngine::TIER_VERY_HIGH));
        $this->assertEquals(2.50, DemandEngine::getIncentiveForTier(DemandEngine::TIER_VERY_HIGH));
        $this->assertEquals(35, DemandEngine::getPointsForTier(DemandEngine::TIER_VERY_HIGH));
    }

    /**
     * Test RouteOptimizationService respect of precedence (Pickup before Dropoff).
     */
    public function test_route_optimization_precedence_and_legs(): void
    {
        $origin = ['lat' => -12.04318, 'lng' => -77.02824];

        $orders = [
            [
                'id'              => 1,
                'type'            => 'delivery',
                'pickup_lat'      => -12.04500,
                'pickup_lng'      => -77.03000,
                'dropoff_lat'     => -12.05000,
                'dropoff_lng'     => -77.03500,
            ],
            [
                'id'              => 2,
                'type'            => 'delivery',
                'pickup_lat'      => -12.04600,
                'pickup_lng'      => -77.03100,
                'dropoff_lat'     => -12.05200,
                'dropoff_lng'     => -77.03800,
            ],
        ];

        $route = RouteOptimizationService::optimizeRoute($origin, $orders);

        $this->assertNotEmpty($route['ordered_stops']);
        $this->assertCount(4, $route['ordered_stops']);
        $this->assertGreaterThan(0, $route['total_distance_km']);
        $this->assertGreaterThan(0, $route['total_duration_minutes']);

        // Verify that for every order, pickup stop appears before dropoff stop
        $positions = [];
        foreach ($route['ordered_stops'] as $index => $stop) {
            $positions[$stop['order_id']][$stop['type']] = $index;
        }

        foreach ($positions as $orderId => $orderStops) {
            $this->assertArrayHasKey('pickup', $orderStops);
            $this->assertArrayHasKey('dropoff', $orderStops);
            $this->assertLessThan(
                $orderStops['dropoff'],
                $orderStops['pickup'],
                "El recojo del pedido {$orderId} debe ocurrir antes de su entrega."
            );
        }
    }

    /**
     * Test DriverFareEngine calculations, batch bonuses, and tip separation.
     */
    public function test_driver_fare_engine_formula_and_tips(): void
    {
        // 1. Single order, 5 km, 15 min, normal demand, tip S/ 2.00
        $rates = [
            'base_fare'           => 3.00,
            'rate_per_km'         => 0.80,
            'rate_per_minute'     => 0.10,
            'batch_double_bonus'  => 1.50,
            'batch_triplet_bonus' => 3.00,
            'batch_quad_bonus'    => 5.00,
        ];

        $singleFare = DriverFareEngine::calculateRouteFare(5.0, 15.0, 1, DemandEngine::TIER_NORMAL, 2.00);

        // base (3.00) + dist (5 * 0.80 = 4.00) + time (15 * 0.10 = 1.50) + batch (0) = 8.50
        $this->assertEquals(8.50, $singleFare['driver_earning']);
        $this->assertEquals(2.00, $singleFare['tip']);
        $this->assertEquals(10.50, $singleFare['total_payout']);
        $this->assertEquals('SINGLE', $singleFare['batch_type']);

        // 2. Doublet batch, 8 km, 25 min, normal demand
        $doubleFare = DriverFareEngine::calculateRouteFare(8.0, 25.0, 2, DemandEngine::TIER_NORMAL, 0.00);
        // base (3.00) + dist (8 * 0.80 = 6.40) + time (25 * 0.10 = 2.50) + batch_double (1.50) = 13.40
        $this->assertEquals(13.40, $doubleFare['driver_earning']);
        $this->assertEquals('DOUBLE', $doubleFare['batch_type']);
        $this->assertEquals(1.50, $doubleFare['batch_bonus']);

        // 3. Quadruple batch with HIGH demand (1.10x + S/ 1.00)
        $quadFare = DriverFareEngine::calculateRouteFare(12.0, 40.0, 4, DemandEngine::TIER_HIGH, 5.00);
        // subtotal = 3.00 + (12 * 0.80 = 9.60) + (40 * 0.10 = 4.00) + 5.00 = 21.60
        // with demand 1.10x: 21.60 * 1.10 = 23.76 + 1.00 incentive = 24.76
        $this->assertEquals(24.76, $quadFare['driver_earning']);
        $this->assertEquals(5.00, $quadFare['tip']);
        $this->assertEquals(29.76, $quadFare['total_payout']);
        $this->assertEquals('QUADRUPLE', $quadFare['batch_type']);
    }

    /**
     * Test BatchingEngine compatibility validation.
     */
    public function test_batching_engine_max_capacity(): void
    {
        $existingOrders = [
            ['id' => 1, 'pickup_lat' => -12.045, 'pickup_lng' => -77.030, 'dropoff_lat' => -12.050, 'dropoff_lng' => -77.035],
            ['id' => 2, 'pickup_lat' => -12.046, 'pickup_lng' => -77.031, 'dropoff_lat' => -12.051, 'dropoff_lng' => -77.036],
            ['id' => 3, 'pickup_lat' => -12.047, 'pickup_lng' => -77.032, 'dropoff_lat' => -12.052, 'dropoff_lng' => -77.037],
            ['id' => 4, 'pickup_lat' => -12.048, 'pickup_lng' => -77.033, 'dropoff_lat' => -12.053, 'dropoff_lng' => -77.038],
        ];

        $candidate = [
            'id'          => 5,
            'pickup_lat'  => -12.045,
            'pickup_lng'  => -77.030,
            'dropoff_lat' => -12.050,
            'dropoff_lng' => -77.035,
        ];

        $check = BatchingEngine::canBatchWith($existingOrders, $candidate, ['lat' => -12.043, 'lng' => -77.028]);
        $this->assertFalse($check['compatible']);
        $this->assertStringContainsString('máximo 4', $check['reason']);
    }
}
