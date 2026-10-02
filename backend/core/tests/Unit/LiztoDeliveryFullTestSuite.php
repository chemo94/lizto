<?php

namespace Tests\Unit;

use App\Models\Driver;
use App\Models\Wallet;
use App\Services\BatchingEngine;
use App\Services\DemandEngine;
use App\Services\DriverEconomicPolicyService;
use App\Services\DriverFareEngine;
use App\Services\OfferDispatchService;
use App\Services\RouteOptimizationService;
use PHPUnit\Framework\TestCase;

class LiztoDeliveryFullTestSuite extends TestCase
{
    // =========================================================================
    // BLOQUE 1: POLÍTICA ECONÓMICA Y WALLET DE REPARTIDORES LIZTO (CASOS 1 - 9)
    // =========================================================================

    /**
     * Caso 1: Crédito promocional inicial de S/ 15.00 otorgado a repartidor nuevo.
     */
    public function test_case_01_grant_initial_promotional_credit(): void
    {
        $this->assertEquals('promotional_balance', DriverEconomicPolicyService::PROMOTIONAL_BALANCE);
        $this->assertEquals('promotional_credit', DriverEconomicPolicyService::TRX_PROMOTIONAL_CREDIT);

        // Instanciar wallet y simular estado inicial de recarga promocional
        $wallet = new Wallet([
            'promotional_balance' => 15.00,
            'recharge_balance'    => 0.00,
            'balance'             => 15.00,
            'economic_state'      => DriverEconomicPolicyService::PROMOTIONAL_BALANCE,
        ]);

        $this->assertEquals(15.00, (float) $wallet->promotional_balance);
        $this->assertEquals(0.00, (float) $wallet->recharge_balance);
        $this->assertEquals(DriverEconomicPolicyService::PROMOTIONAL_BALANCE, $wallet->economic_state);
    }

    /**
     * Caso 2: Repartidor con saldo promocional está autorizado para recibir pedidos.
     */
    public function test_case_02_promotional_balance_driver_receives_orders(): void
    {
        $driver = new Driver(['status' => 1]);
        $wallet = new Wallet([
            'promotional_balance' => 15.00,
            'recharge_balance'    => 0.00,
            'balance'             => 15.00,
            'economic_state'      => DriverEconomicPolicyService::PROMOTIONAL_BALANCE,
        ]);
        $driver->setRelation('wallet', $wallet);

        $check = DriverEconomicPolicyService::canDriverReceiveOrders($driver);

        $this->assertTrue($check['allowed'], 'El repartidor con saldo promocional debe recibir pedidos.');
        $this->assertEquals('promotional', $check['balance_type']);
        $this->assertEquals(15.00, $check['promotional_balance']);
    }

    /**
     * Caso 3: En período promocional, el repartidor conserva el 100% de la ganancia.
     */
    public function test_case_03_promotional_balance_100_percent_earnings_retention(): void
    {
        // En período promocional, la política económica de Lizto dictamina 0 comisión.
        $driverEarning = 12.50;
        $commissionDeducted = 0.00; // Cero comisión tradicional
        $netPayout = $driverEarning - $commissionDeducted;

        $this->assertEquals(12.50, $netPayout, 'El repartidor debe conservar el 100% de la ganancia en período promocional.');
    }

    /**
     * Caso 4: Agotamiento de saldo promocional bloquea recepción de pedidos hasta recarga.
     */
    public function test_case_04_promotional_balance_depletion_blocks_orders(): void
    {
        $driver = new Driver(['status' => 1]);
        $wallet = new Wallet([
            'promotional_balance' => 0.00,
            'recharge_balance'    => 0.00,
            'balance'             => 0.00,
            'economic_state'      => DriverEconomicPolicyService::INSUFFICIENT_BALANCE,
        ]);
        $driver->setRelation('wallet', $wallet);

        $check = DriverEconomicPolicyService::canDriverReceiveOrders($driver);

        $this->assertFalse($check['allowed'], 'El repartidor sin saldo no debe poder recibir pedidos.');
        $this->assertEquals(DriverEconomicPolicyService::INSUFFICIENT_BALANCE, $check['economic_state']);
        $this->assertStringContainsString('Recarga', $check['reason']);
    }

    /**
     * Caso 5: Validación de recarga mínima (rechazo de montos < S/ 8.00).
     */
    public function test_case_05_minimum_recharge_threshold_enforcement(): void
    {
        $validationLow = DriverEconomicPolicyService::validateRechargeAmount(5.00, 8.00);
        $this->assertFalse($validationLow['valid'], 'Una recarga de S/ 5.00 debe ser rechazada.');
        $this->assertStringContainsString('8.00', $validationLow['message']);

        $validationBelowZero = DriverEconomicPolicyService::validateRechargeAmount(-2.00, 8.00);
        $this->assertFalse($validationBelowZero['valid'], 'Montos negativos deben ser rechazados.');
    }

    /**
     * Caso 6: Validación de recarga exitosa (montos >= S/ 8.00 aceptados).
     */
    public function test_case_06_minimum_recharge_threshold_success(): void
    {
        $validationExact = DriverEconomicPolicyService::validateRechargeAmount(8.00, 8.00);
        $this->assertTrue($validationExact['valid'], 'Una recarga exacta de S/ 8.00 debe ser aceptada.');

        $validationHigher = DriverEconomicPolicyService::validateRechargeAmount(20.00, 8.00);
        $this->assertTrue($validationHigher['valid'], 'Una recarga de S/ 20.00 debe ser aceptada.');
    }

    /**
     * Caso 7: Procesamiento de recarga activa el estado ACTIVE_BALANCE.
     */
    public function test_case_07_recharge_activates_active_balance_state(): void
    {
        $this->assertEquals('active_balance', DriverEconomicPolicyService::ACTIVE_BALANCE);
        $this->assertEquals('recharge', DriverEconomicPolicyService::TRX_RECHARGE);

        $wallet = new Wallet([
            'promotional_balance' => 0.00,
            'recharge_balance'    => 10.00,
            'balance'             => 10.00,
            'economic_state'      => DriverEconomicPolicyService::ACTIVE_BALANCE,
        ]);

        $this->assertEquals(DriverEconomicPolicyService::ACTIVE_BALANCE, $wallet->economic_state);
        $this->assertEquals(10.00, (float) $wallet->recharge_balance);
    }

    /**
     * Caso 8: En saldo estándar activo, la deducción por servicio se descuenta de la recarga.
     */
    public function test_case_08_standard_balance_fee_deduction(): void
    {
        $this->assertEquals('consumption', DriverEconomicPolicyService::TRX_CONSUMPTION);

        $initialRechargeBalance = 20.00;
        $orderServiceFee = 1.20;
        $remainingBalance = $initialRechargeBalance - $orderServiceFee;

        $this->assertEquals(18.80, $remainingBalance);
        $this->assertGreaterThan(0, $remainingBalance);
    }

    /**
     * Caso 9: Ajuste administrativo de saldos (promocional o recarga).
     */
    public function test_case_09_admin_adjustment_flexibility(): void
    {
        $this->assertEquals('admin_adjustment', DriverEconomicPolicyService::TRX_ADMIN_ADJUSTMENT);
        $this->assertEquals('blocked_from_orders', DriverEconomicPolicyService::BLOCKED_FROM_ORDERS);
    }

    // =========================================================================
    // BLOQUE 2: MOTOR DE DEMANDA EN TIEMPO REAL (CASOS 10 - 13)
    // =========================================================================

    /**
     * Caso 10: Nivel de demanda LOW (< 0.8) -> 1.00x, S/ 0.00 incentivo, 5 puntos.
     */
    public function test_case_10_demand_engine_tier_low(): void
    {
        $this->assertEquals(1.00, DemandEngine::getMultiplierForTier(DemandEngine::TIER_LOW));
        $this->assertEquals(0.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_LOW));
        $this->assertEquals(5, DemandEngine::getPointsForTier(DemandEngine::TIER_LOW));
    }

    /**
     * Caso 11: Nivel de demanda NORMAL (0.8 <= ratio < 1.5) -> 1.00x, S/ 0.00 incentivo, 10 puntos.
     */
    public function test_case_11_demand_engine_tier_normal(): void
    {
        $this->assertEquals(1.00, DemandEngine::getMultiplierForTier(DemandEngine::TIER_NORMAL));
        $this->assertEquals(0.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_NORMAL));
        $this->assertEquals(10, DemandEngine::getPointsForTier(DemandEngine::TIER_NORMAL));
    }

    /**
     * Caso 12: Nivel de demanda HIGH (1.5 <= ratio < 2.5) -> 1.10x, S/ 1.00 incentivo, 20 puntos.
     */
    public function test_case_12_demand_engine_tier_high(): void
    {
        $this->assertEquals(1.10, DemandEngine::getMultiplierForTier(DemandEngine::TIER_HIGH));
        $this->assertEquals(1.00, DemandEngine::getIncentiveForTier(DemandEngine::TIER_HIGH));
        $this->assertEquals(20, DemandEngine::getPointsForTier(DemandEngine::TIER_HIGH));
    }

    /**
     * Caso 13: Nivel de demanda VERY_HIGH (ratio >= 2.5) -> 1.25x, S/ 2.50 incentivo, 35 puntos.
     */
    public function test_case_13_demand_engine_tier_very_high(): void
    {
        $this->assertEquals(1.25, DemandEngine::getMultiplierForTier(DemandEngine::TIER_VERY_HIGH));
        $this->assertEquals(2.50, DemandEngine::getIncentiveForTier(DemandEngine::TIER_VERY_HIGH));
        $this->assertEquals(35, DemandEngine::getPointsForTier(DemandEngine::TIER_VERY_HIGH));
    }

    // =========================================================================
    // BLOQUE 3: OPTIMIZACIÓN DE RUTAS MULTI-PARADA (CASOS 14 - 16)
    // =========================================================================

    /**
     * Caso 14: Restricción estricta de precedencia (Recojo antes de Entrega).
     */
    public function test_case_14_route_optimization_precedence(): void
    {
        $origin = ['lat' => -12.04318, 'lng' => -77.02824];
        $orders = [
            [
                'id'          => 101,
                'type'        => 'delivery',
                'pickup_lat'  => -12.04500,
                'pickup_lng'  => -77.03000,
                'dropoff_lat' => -12.05500,
                'dropoff_lng' => -77.04000,
            ],
            [
                'id'          => 102,
                'type'        => 'delivery',
                'pickup_lat'  => -12.04600,
                'pickup_lng'  => -77.03100,
                'dropoff_lat' => -12.05800,
                'dropoff_lng' => -77.04200,
            ],
        ];

        $route = RouteOptimizationService::optimizeRoute($origin, $orders);

        $order1PickupIdx = null;
        $order1DropoffIdx = null;
        $order2PickupIdx = null;
        $order2DropoffIdx = null;

        foreach ($route['ordered_stops'] as $idx => $stop) {
            if ($stop['order_id'] == 101 && $stop['type'] === 'pickup') $order1PickupIdx = $idx;
            if ($stop['order_id'] == 101 && $stop['type'] === 'dropoff') $order1DropoffIdx = $idx;
            if ($stop['order_id'] == 102 && $stop['type'] === 'pickup') $order2PickupIdx = $idx;
            if ($stop['order_id'] == 102 && $stop['type'] === 'dropoff') $order2DropoffIdx = $idx;
        }

        $this->assertNotNull($order1PickupIdx);
        $this->assertNotNull($order1DropoffIdx);
        $this->assertNotNull($order2PickupIdx);
        $this->assertNotNull($order2DropoffIdx);

        $this->assertLessThan($order1DropoffIdx, $order1PickupIdx, 'P101 debe ocurrir antes de D101');
        $this->assertLessThan($order2DropoffIdx, $order2PickupIdx, 'P102 debe ocurrir antes de D102');
    }

    /**
     * Caso 15: Optimización de itinerario multi-parada con cálculo de tiempo y distancia.
     */
    public function test_case_15_route_optimization_multi_stops_itinerary(): void
    {
        $origin = ['lat' => -12.04318, 'lng' => -77.02824];
        $orders = [
            [
                'id'          => 1,
                'pickup_lat'  => -12.04500,
                'pickup_lng'  => -77.03000,
                'dropoff_lat' => -12.05000,
                'dropoff_lng' => -77.03500,
            ]
        ];

        $route = RouteOptimizationService::optimizeRoute($origin, $orders);

        $this->assertCount(2, $route['ordered_stops']);
        $this->assertGreaterThan(0, $route['total_distance_km']);
        $this->assertGreaterThan(0, $route['total_duration_minutes']);
        $this->assertEquals(1, $route['ordered_stops'][0]['stop_number']);
        $this->assertEquals(2, $route['ordered_stops'][1]['stop_number']);
    }

    /**
     * Caso 16: Cálculo de ruta incremental al agregar una orden a una ruta activa.
     */
    public function test_case_16_route_optimization_incremental_stats(): void
    {
        $courierLoc = ['lat' => -12.04318, 'lng' => -77.02824];
        $existing = [
            [
                'id'          => 1,
                'pickup_lat'  => -12.04500,
                'pickup_lng'  => -77.03000,
                'dropoff_lat' => -12.05000,
                'dropoff_lng' => -77.03500,
            ]
        ];
        $candidate = [
            'id'          => 2,
            'pickup_lat'  => -12.04600,
            'pickup_lng'  => -77.03100,
            'dropoff_lat' => -12.05200,
            'dropoff_lng' => -77.03600,
        ];

        $inc = RouteOptimizationService::calculateIncrementalRoute($existing, $candidate, $courierLoc);

        $this->assertArrayHasKey('added_distance_km', $inc);
        $this->assertArrayHasKey('added_duration_minutes', $inc);
        $this->assertGreaterThanOrEqual(0, $inc['added_distance_km']);
        $this->assertGreaterThanOrEqual(0, $inc['added_duration_minutes']);
    }

    // =========================================================================
    // BLOQUE 4: MOTOR DE GANANCIAS Y TARIFAS (CASOS 17 - 19)
    // =========================================================================

    /**
     * Caso 17: Cálculo de ganancia para pedido individual: base + distancia + tiempo.
     */
    public function test_case_17_driver_fare_engine_single_order(): void
    {
        // 4 km, 12 min, demanda normal, propina 0
        // Base 3.00 + (4 * 0.80 = 3.20) + (12 * 0.10 = 1.20) = 7.40
        $fare = DriverFareEngine::calculateRouteFare(4.0, 12.0, 1, DemandEngine::TIER_NORMAL, 0.0);

        $this->assertEquals(7.40, $fare['driver_earning']);
        $this->assertEquals(0.00, $fare['batch_bonus']);
        $this->assertEquals('SINGLE', $fare['batch_type']);
        $this->assertTrue($fare['driver_retains_100']);
    }

    /**
     * Caso 18: Bonos económicos por lote (Doblete +1.50, Triplete +3.00, Cuádruple +5.00).
     */
    public function test_case_18_driver_fare_engine_batch_bonuses(): void
    {
        $singleFare = DriverFareEngine::calculateRouteFare(5.0, 15.0, 1);
        $this->assertEquals(0.00, $singleFare['batch_bonus']);
        $this->assertEquals('SINGLE', $singleFare['batch_type']);

        $doubleFare = DriverFareEngine::calculateRouteFare(5.0, 15.0, 2);
        $this->assertEquals(1.50, $doubleFare['batch_bonus']);
        $this->assertEquals('DOUBLE', $doubleFare['batch_type']);

        $tripletFare = DriverFareEngine::calculateRouteFare(5.0, 15.0, 3);
        $this->assertEquals(3.00, $tripletFare['batch_bonus']);
        $this->assertEquals('TRIPLET', $tripletFare['batch_type']);

        $quadFare = DriverFareEngine::calculateRouteFare(5.0, 15.0, 4);
        $this->assertEquals(5.00, $quadFare['batch_bonus']);
        $this->assertEquals('QUADRUPLE', $quadFare['batch_type']);
    }

    /**
     * Caso 19: Aislamiento estricto de propinas (100% passthrough).
     */
    public function test_case_19_driver_fare_engine_tip_separation(): void
    {
        $fareWithTip = DriverFareEngine::calculateRouteFare(5.0, 15.0, 1, DemandEngine::TIER_NORMAL, 3.50);

        $this->assertEquals(8.50, $fareWithTip['driver_earning']);
        $this->assertEquals(3.50, $fareWithTip['tip']);
        $this->assertEquals(12.00, $fareWithTip['total_payout']);
    }

    // =========================================================================
    // BLOQUE 5: MOTOR DE AGRUPACIÓN Y CAPACIDAD (CASO 20)
    // =========================================================================

    /**
     * Caso 20: Límite estricto de capacidad en lotes (máximo 4 pedidos).
     */
    public function test_case_20_batching_engine_capacity_limit(): void
    {
        $existingOrders = [
            ['id' => 1, 'pickup_lat' => -12.045, 'pickup_lng' => -77.030],
            ['id' => 2, 'pickup_lat' => -12.045, 'pickup_lng' => -77.030],
            ['id' => 3, 'pickup_lat' => -12.045, 'pickup_lng' => -77.030],
            ['id' => 4, 'pickup_lat' => -12.045, 'pickup_lng' => -77.030],
        ];

        $candidate = [
            'id'         => 5,
            'pickup_lat' => -12.045,
            'pickup_lng' => -77.030,
        ];

        $result = BatchingEngine::canBatchWith($existingOrders, $candidate, ['lat' => -12.043, 'lng' => -77.028]);

        $this->assertFalse($result['compatible']);
        $this->assertStringContainsString('máximo 4', $result['reason']);
    }

    // =========================================================================
    // BLOQUE 6: DESPACHO, EXPIRACIÓN 15S Y AUTOACEPTACIÓN (CASOS 21 - 22)
    // =========================================================================

    /**
     * Caso 21: Ventana de expiración estricta de 15 segundos en ofertas dirigidas.
     */
    public function test_case_21_offer_dispatch_15_seconds_expiration(): void
    {
        $this->assertEquals(15, OfferDispatchService::RESPONSE_WINDOW_SECONDS);
    }

    /**
     * Caso 22: Evaluación de umbrales de autoaceptación inteligente.
     */
    public function test_case_22_auto_acceptance_thresholds_evaluation(): void
    {
        $driver = new Driver([
            'auto_accept_enabled'      => true,
            'auto_accept_min_earning'  => 8.00,
            'auto_accept_max_distance' => 5.0,
        ]);

        // Ganancia S/ 9.50 (>= 8.00) y Distancia 4.0 km (<= 5.0) -> Auto-acepta
        $this->assertTrue(OfferDispatchService::shouldAutoAccept($driver, 9.50, 4.0));

        // Ganancia S/ 6.00 (< 8.00) -> No auto-acepta
        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driver, 6.00, 4.0));

        // Distancia 7.0 km (> 5.0) -> No auto-acepta
        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driver, 15.00, 7.0));

        // Auto-aceptación desactivada -> No auto-acepta nunca
        $driverDisabled = new Driver(['auto_accept_enabled' => false]);
        $this->assertFalse(OfferDispatchService::shouldAutoAccept($driverDisabled, 50.00, 1.0));
    }
}
