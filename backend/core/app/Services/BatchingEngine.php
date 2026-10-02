<?php

namespace App\Services;

use App\Models\CourierBatch;
use App\Models\CourierBatchOrder;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Favor;
use App\Support\DeliveryPricing;
use Illuminate\Support\Facades\DB;

class BatchingEngine
{
    public const MAX_BATCH_SIZE = 4;

    /**
     * Check if a candidate order can be batched with existing orders for a courier.
     */
    public static function canBatchWith(array $existingOrders, array $candidateOrder, array $courierLocation): array
    {
        $currentCount = count($existingOrders);
        if ($currentCount >= self::MAX_BATCH_SIZE) {
            return [
                'compatible' => false,
                'reason'     => 'Capacidad máxima de lote alcanzada (máximo 4 pedidos).',
                'incremental'=> null,
            ];
        }

        $maxPickupDist = (float) (gs('max_batch_pickup_distance_km') ?? 1.50);
        $maxDetourKm   = (float) (gs('max_batch_detour_km') ?? 3.00);
        $maxDetourMin  = (float) (gs('max_batch_detour_minutes') ?? 15.00);

        // 1. Check pickup proximity against at least one existing pickup
        $candPLat = (float) ($candidateOrder['pickup_lat'] ?? 0);
        $candPLng = (float) ($candidateOrder['pickup_lng'] ?? 0);

        $pickupWithinRange = false;
        foreach ($existingOrders as $existing) {
            $exPLat = (float) ($existing['pickup_lat'] ?? 0);
            $exPLng = (float) ($existing['pickup_lng'] ?? 0);

            $pickupDist = DeliveryPricing::distanceKm($candPLat, $candPLng, $exPLat, $exPLng);
            if ($pickupDist <= $maxPickupDist) {
                $pickupWithinRange = true;
                break;
            }
        }

        if (!$pickupWithinRange) {
            return [
                'compatible' => false,
                'reason'     => "El punto de recojo supera el radio permitido de {$maxPickupDist} km respecto a los pedidos actuales.",
                'incremental'=> null,
            ];
        }

        // 2. Calculate detour impact
        $incremental = RouteOptimizationService::calculateIncrementalRoute(
            $existingOrders,
            $candidateOrder,
            $courierLocation
        );

        if ($incremental['added_distance_km'] > $maxDetourKm) {
            return [
                'compatible' => false,
                'reason'     => "El desvío de {$incremental['added_distance_km']} km excede el límite permitido de {$maxDetourKm} km.",
                'incremental'=> $incremental,
            ];
        }

        if ($incremental['added_duration_minutes'] > $maxDetourMin) {
            return [
                'compatible' => false,
                'reason'     => "El tiempo adicional de {$incremental['added_duration_minutes']} min excede el límite permitido de {$maxDetourMin} min.",
                'incremental'=> $incremental,
            ];
        }

        return [
            'compatible' => true,
            'reason'     => null,
            'incremental'=> $incremental,
        ];
    }

    /**
     * Create and persist a new CourierBatch from a set of orders.
     *
     * @param array $orders Array of DeliveryOrder, Favor, or normalized order arrays.
     * @param Driver|null $driver
     * @param array|null $courierLocation ['lat' => float, 'lng' => float]
     * @return CourierBatch
     */
    public static function createBatch(array $orders, ?Driver $driver = null, ?array $courierLocation = null): CourierBatch
    {
        if (empty($orders)) {
            throw new \InvalidArgumentException('No se pueden crear lotes sin pedidos.');
        }

        if (count($orders) > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException('Un lote no puede superar 4 pedidos.');
        }

        // 1. Normalize orders
        $normalized = [];
        $rawOrderMap = [];
        $totalTips = 0.0;

        foreach ($orders as $idx => $order) {
            $norm = self::normalizeOrder($order);
            $normalized[] = $norm;
            $rawOrderMap[$idx] = $order;
            $totalTips += (float) ($norm['tip'] ?? 0);
        }

        // Origin defaults to courier location, or first pickup
        $origin = $courierLocation;
        if (!$origin || empty($origin['lat']) || empty($origin['lng'])) {
            $origin = [
                'lat' => (float) ($normalized[0]['pickup_lat'] ?? -12.04318),
                'lng' => (float) ($normalized[0]['pickup_lng'] ?? -77.02824),
            ];
        }

        // 2. Optimize multi-stop itinerary
        $route = RouteOptimizationService::optimizeRoute($origin, $normalized);

        // 3. Demand assessment
        $demand = DemandEngine::calculateDemandTier($origin['lat'], $origin['lng']);

        // 4. Calculate dynamic fare breakdown
        $orderCount = count($normalized);
        $fare = DriverFareEngine::calculateRouteFare(
            $route['total_distance_km'],
            $route['total_duration_minutes'],
            $orderCount,
            $demand['tier'],
            $totalTips
        );

        $batchType = CourierBatch::determineType($orderCount);

        return DB::transaction(function () use (
            $driver, $normalized, $rawOrderMap, $route, $demand, $fare, $batchType, $orderCount, $totalTips
        ) {
            // 5. Persist CourierBatch
            $batch = CourierBatch::create([
                'batch_no'               => CourierBatch::generateBatchNo(),
                'driver_id'              => $driver?->id,
                'batch_type'             => $batchType,
                'status'                 => $driver ? CourierBatch::STATUS_ACCEPTED : CourierBatch::STATUS_PENDING,
                'total_orders'           => $orderCount,
                'total_distance_km'      => $route['total_distance_km'],
                'total_duration_minutes' => $route['total_duration_minutes'],
                'base_earning'           => $fare['base_fare'],
                'distance_earning'       => $fare['distance_amount'],
                'time_earning'           => $fare['time_amount'],
                'batch_bonus'            => $fare['batch_bonus'],
                'demand_incentive'       => $fare['demand_incentive'],
                'driver_earning'         => $fare['driver_earning'],
                'total_tips'             => $totalTips,
                'total_payout'           => $fare['total_payout'],
                'total_points'           => $fare['points'],
                'demand_tier'            => $demand['tier'],
                'demand_multiplier'      => $demand['multiplier'],
                'optimized_stops'        => $route['ordered_stops'],
                'fare_breakdown'         => $fare,
                'accepted_at'            => $driver ? now() : null,
            ]);

            // 6. Persist CourierBatchOrders & associate
            $perOrderEarning = round($fare['driver_earning'] / $orderCount, 2);
            $perOrderPoints  = (int) floor($fare['points'] / $orderCount);

            foreach ($normalized as $idx => $norm) {
                // Find stop numbers from optimized route
                $pickupStopNo = 1;
                $dropoffStopNo = 2;
                foreach ($route['ordered_stops'] as $s) {
                    if ($s['order_id'] == $norm['id'] && $s['type'] === 'pickup') {
                        $pickupStopNo = $s['stop_number'];
                    }
                    if ($s['order_id'] == $norm['id'] && $s['type'] === 'dropoff') {
                        $dropoffStopNo = $s['stop_number'];
                    }
                }

                $orderTip = (float) ($norm['tip'] ?? 0);
                $orderModelClass = $norm['type'] === 'favor' ? Favor::class : DeliveryOrder::class;

                $batchOrder = CourierBatchOrder::create([
                    'batch_id'           => $batch->id,
                    'order_id'           => $norm['id'],
                    'order_type'         => $orderModelClass,
                    'sequence_order'     => $idx + 1,
                    'pickup_stop_no'     => $pickupStopNo,
                    'dropoff_stop_no'    => $dropoffStopNo,
                    'status'             => CourierBatchOrder::STATUS_PENDING,
                    'individual_earning' => $perOrderEarning,
                    'tip'                => $orderTip,
                    'points'             => $perOrderPoints,
                    'fare_breakdown'     => [
                        'allocated_earning' => $perOrderEarning,
                        'tip'               => $orderTip,
                        'total'             => round($perOrderEarning + $orderTip, 2),
                    ],
                ]);

                // Link to raw order model
                $raw = $rawOrderMap[$idx];
                if ($raw instanceof DeliveryOrder || $raw instanceof Favor) {
                    $raw->update([
                        'courier_batch_id' => $batch->id,
                        'driver_id'        => $driver?->id ?? ($raw instanceof DeliveryOrder ? $raw->driver_id : null),
                    ]);
                    if ($raw instanceof Favor && $driver) {
                        $raw->update(['courier_id' => $driver->id]);
                    }
                }
            }

            return $batch->fresh(['batchOrders', 'driver']);
        });
    }

    /**
     * Mark an order in a batch as delivered and finalize batch if all orders completed.
     * Ensures adherence to Lizto's economic policy (no multi-commission penalty on batches).
     */
    public static function completeOrderInBatch(CourierBatchOrder $batchOrder): void
    {
        DB::transaction(function () use ($batchOrder) {
            $batchOrder->update([
                'status'       => CourierBatchOrder::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            $batch = $batchOrder->batch()->lockForUpdate()->first();
            if (!$batch) return;

            // Check if all orders in batch are delivered
            $pendingCount = CourierBatchOrder::where('batch_id', $batch->id)
                ->where('status', '!=', CourierBatchOrder::STATUS_DELIVERED)
                ->where('status', '!=', CourierBatchOrder::STATUS_CANCELLED)
                ->count();

            if ($pendingCount === 0) {
                $batch->update([
                    'status'       => CourierBatch::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        });
    }

    /**
     * Normalize DeliveryOrder, Favor or array into unified structure for routing & fare engine.
     */
    public static function normalizeOrder($order): array
    {
        if (is_array($order)) {
            return [
                'id'              => $order['id'] ?? null,
                'type'            => $order['type'] ?? 'delivery',
                'order_no'        => $order['order_no'] ?? ('#' . ($order['id'] ?? '0')),
                'pickup_lat'      => (float) ($order['pickup_lat'] ?? 0),
                'pickup_lng'      => (float) ($order['pickup_lng'] ?? 0),
                'pickup_address'  => $order['pickup_address'] ?? 'Punto de recojo',
                'pickup_name'     => $order['pickup_name'] ?? 'Restaurante / Negocio',
                'dropoff_lat'     => (float) ($order['dropoff_lat'] ?? 0),
                'dropoff_lng'     => (float) ($order['dropoff_lng'] ?? 0),
                'dropoff_address' => $order['dropoff_address'] ?? 'Dirección de destino',
                'dropoff_name'    => $order['dropoff_name'] ?? 'Cliente final',
                'tip'             => (float) ($order['tip'] ?? 0),
                'total'           => (float) ($order['total'] ?? 0),
            ];
        }

        if ($order instanceof DeliveryOrder) {
            $store = $order->store;
            $user  = $order->user;
            return [
                'id'              => $order->id,
                'type'            => 'delivery',
                'order_no'        => $order->order_no ?? ('ORD-' . $order->id),
                'pickup_lat'      => (float) ($store?->latitude ?? 0),
                'pickup_lng'      => (float) ($store?->longitude ?? 0),
                'pickup_address'  => $store?->address ?? 'Tienda',
                'pickup_name'     => $store?->name ?? 'Restaurante',
                'dropoff_lat'     => (float) ($order->delivery_lat ?? 0),
                'dropoff_lng'     => (float) ($order->delivery_lng ?? 0),
                'dropoff_address' => $order->delivery_address ?? 'Destino del cliente',
                'dropoff_name'    => $user ? trim($user->firstname . ' ' . $user->lastname) : 'Cliente',
                'tip'             => (float) ($order->tip ?? 0),
                'total'           => (float) ($order->total ?? 0),
            ];
        }

        if ($order instanceof Favor) {
            $user = $order->user;
            return [
                'id'              => $order->id,
                'type'            => 'favor',
                'order_no'        => $order->order_no ?? ('FAV-' . $order->id),
                'pickup_lat'      => (float) ($order->pickup_lat ?? 0),
                'pickup_lng'      => (float) ($order->pickup_lng ?? 0),
                'pickup_address'  => $order->pickup_address ?? 'Punto de recojo',
                'pickup_name'     => $order->pickup_name ?? 'Remitente',
                'dropoff_lat'     => (float) ($order->delivery_lat ?? 0),
                'dropoff_lng'     => (float) ($order->delivery_lng ?? 0),
                'dropoff_address' => $order->delivery_address ?? 'Destino de entrega',
                'dropoff_name'    => $user ? trim($user->firstname . ' ' . $user->lastname) : ($order->receiver_name ?? 'Destinatario'),
                'tip'             => 0.0,
                'total'           => (float) ($order->total ?? $order->delivery_fee ?? 0),
            ];
        }

        throw new \InvalidArgumentException('Formato de orden desconocido para normalización.');
    }
}
