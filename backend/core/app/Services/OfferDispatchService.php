<?php

namespace App\Services;

use App\Constants\Status;
use App\Events\CourierOfferAutoAccepted;
use App\Events\CourierOfferCreated;
use App\Events\CourierOfferExpired;
use App\Events\DeliveryOrderStatusUpdated;
use App\Events\FavorStatusUpdated;
use App\Models\CourierBatch;
use App\Models\CourierJobOffer;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Favor;
use App\Support\DeliveryPricing;
use Illuminate\Support\Facades\DB;

class OfferDispatchService
{
    public const RESPONSE_WINDOW_SECONDS = 15;

    /**
     * Dispatch a single order (DeliveryOrder or Favor) to the best eligible courier.
     */
    public static function dispatchOrder($order): array
    {
        $normalized = BatchingEngine::normalizeOrder($order);
        $pickupLat = (float) $normalized['pickup_lat'];
        $pickupLng = (float) $normalized['pickup_lng'];
        $deliveryLat = (float) $normalized['dropoff_lat'];
        $deliveryLng = (float) $normalized['dropoff_lng'];

        $distanceKm = round(DeliveryPricing::distanceKm($pickupLat, $pickupLng, $deliveryLat, $deliveryLng), 2);
        $durationMin = RouteOptimizationService::estimateTimeMinutes($distanceKm);
        $demand = DemandEngine::calculateDemandTier($pickupLat, $pickupLng);
        $fare = DriverFareEngine::calculateRouteFare($distanceKm, $durationMin, 1, $demand['tier'], (float) ($normalized['tip'] ?? 0));

        $attemptedDriverIds = collect($order->dispatch_attempted_driver_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        // 1. Find candidates filtered by economic eligibility and proximity
        $candidateDriver = self::findNextEligibleDriver($pickupLat, $pickupLng, $attemptedDriverIds);

        if (!$candidateDriver) {
            // If all attempted, reset list for a second round
            if (!empty($attemptedDriverIds)) {
                $candidateDriver = self::findNextEligibleDriver($pickupLat, $pickupLng, []);
                $attemptedDriverIds = [];
            }
        }

        if (!$candidateDriver) {
            return [
                'status'         => 'no_couriers_available',
                'auto_accepted'  => false,
                'offered'        => false,
                'driver'         => null,
                'message'        => 'No hay repartidores disponibles con saldo activo en la zona.',
            ];
        }

        $attemptedDriverIds[] = $candidateDriver->id;
        $order->update([
            'dispatch_attempted_driver_ids' => array_values(array_unique($attemptedDriverIds)),
        ]);

        $driverEarning = (float) $fare['driver_earning'];
        $totalDistance = (float) $distanceKm;

        // 2. Evaluate Auto-Acceptance
        if (self::shouldAutoAccept($candidateDriver, $driverEarning, $totalDistance)) {
            return self::executeAutoAcceptance($candidateDriver, $order, $fare, $normalized);
        }

        // 3. Dispatch with 15-second Offer Countdown
        $expiresAt = now()->addSeconds(self::RESPONSE_WINDOW_SECONDS);
        $order->update([
            'dispatch_timeout_at' => $expiresAt,
        ]);

        $offerDetails = [
            'type'             => $order instanceof DeliveryOrder ? 'delivery' : 'favor',
            'order_id'         => $order->id,
            'order_no'         => $normalized['order_no'],
            'pickup_address'   => $normalized['pickup_address'],
            'pickup_name'      => $normalized['pickup_name'],
            'dropoff_address'  => $normalized['dropoff_address'],
            'dropoff_name'     => $normalized['dropoff_name'],
            'distance_km'      => $distanceKm,
            'duration_minutes' => $durationMin,
            'driver_earning'   => $driverEarning,
            'tip'              => (float) ($normalized['tip'] ?? 0),
            'total_payout'     => (float) $fare['total_payout'],
            'points'           => (int) $fare['points'],
            'demand_tier'      => $demand['tier'],
            'fare_breakdown'   => $fare,
            'remaining_seconds'=> self::RESPONSE_WINDOW_SECONDS,
        ];

        // Send FCM notification
        $notified = FcmService::sendToDriver(
            $candidateDriver,
            '⚡ ¡Nueva oferta de entrega! (15s)',
            "Gana S/ " . number_format($fare['total_payout'], 2) . " — {$distanceKm} km. Acepta en los próximos 15 segundos.",
            array_merge(FcmService::courierJobPayload($order), [
                'type'              => 'targeted_15s_offer',
                'expires_at'        => $expiresAt->toIso8601String(),
                'remaining_seconds' => (string) self::RESPONSE_WINDOW_SECONDS,
                'total_payout'      => (string) $fare['total_payout'],
            ])
        );

        $offer = CourierOfferTracker::offered(
            $candidateDriver,
            $order,
            'targeted_15s',
            (bool) $notified,
            $expiresAt
        );

        // Broadcast WebSocket event
        event(new CourierOfferCreated($candidateDriver, $offer, $offerDetails));

        return [
            'status'         => 'offered',
            'auto_accepted'  => false,
            'offered'        => true,
            'driver'         => $candidateDriver,
            'offer_id'       => $offer->id,
            'expires_at'     => $expiresAt->toIso8601String(),
            'fare_breakdown' => $fare,
        ];
    }

    /**
     * Dispatch an entire multi-order batch with 15-second expiration or auto-acceptance.
     */
    public static function dispatchBatch(CourierBatch $batch): array
    {
        $firstStop = ($batch->optimized_stops ?? [])[0] ?? null;
        $pickupLat = (float) ($firstStop['lat'] ?? -12.04318);
        $pickupLng = (float) ($firstStop['lng'] ?? -77.02824);

        $attemptedDriverIds = [];
        $existingOffers = CourierJobOffer::where('batch_id', $batch->id)->pluck('driver_id')->all();
        $attemptedDriverIds = array_map('intval', $existingOffers);

        $candidateDriver = self::findNextEligibleDriver($pickupLat, $pickupLng, $attemptedDriverIds);

        if (!$candidateDriver && !empty($attemptedDriverIds)) {
            $candidateDriver = self::findNextEligibleDriver($pickupLat, $pickupLng, []);
        }

        if (!$candidateDriver) {
            return [
                'status'        => 'no_couriers_available',
                'auto_accepted' => false,
                'offered'       => false,
                'driver'        => null,
                'message'       => 'No hay repartidores disponibles para este lote.',
            ];
        }

        $totalEarning  = (float) $batch->driver_earning;
        $totalDistance = (float) $batch->total_distance_km;

        // Auto-acceptance evaluation for batch
        if (self::shouldAutoAccept($candidateDriver, $totalEarning, $totalDistance)) {
            return self::executeBatchAutoAcceptance($candidateDriver, $batch);
        }

        $expiresAt = now()->addSeconds(self::RESPONSE_WINDOW_SECONDS);
        $batch->update([
            'status'     => CourierBatch::STATUS_OFFERED,
            'expires_at' => $expiresAt,
        ]);

        $offerDetails = [
            'type'             => 'batch',
            'batch_id'         => $batch->id,
            'batch_no'         => $batch->batch_no,
            'batch_type'       => $batch->batch_type,
            'total_orders'     => (int) $batch->total_orders,
            'total_distance_km'=> (float) $batch->total_distance_km,
            'total_duration_minutes' => (float) $batch->total_duration_minutes,
            'driver_earning'   => $totalEarning,
            'total_tips'       => (float) $batch->total_tips,
            'total_payout'     => (float) $batch->total_payout,
            'total_points'     => (int) $batch->total_points,
            'demand_tier'      => $batch->demand_tier,
            'fare_breakdown'   => $batch->fare_breakdown,
            'optimized_stops'  => $batch->optimized_stops,
            'remaining_seconds'=> self::RESPONSE_WINDOW_SECONDS,
        ];

        $notified = FcmService::sendToDriver(
            $candidateDriver,
            "⚡ ¡Oferta de Lote {$batch->batch_type}! (15s)",
            "Gana S/ " . number_format($batch->total_payout, 2) . " por {$batch->total_orders} pedidos ({$batch->total_distance_km} km).",
            [
                'type'              => 'batch_15s_offer',
                'batch_id'          => (string) $batch->id,
                'batch_no'          => $batch->batch_no,
                'total_payout'      => (string) $batch->total_payout,
                'expires_at'        => $expiresAt->toIso8601String(),
                'remaining_seconds' => (string) self::RESPONSE_WINDOW_SECONDS,
            ]
        );

        $offer = CourierOfferTracker::offered(
            $candidateDriver,
            $batch,
            'batch_15s',
            (bool) $notified,
            $expiresAt
        );

        event(new CourierOfferCreated($candidateDriver, $offer, $offerDetails));

        return [
            'status'        => 'offered',
            'auto_accepted' => false,
            'offered'       => true,
            'driver'        => $candidateDriver,
            'offer_id'      => $offer->id,
            'expires_at'    => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Respond to a pending offer (Accept or Reject).
     */
    public static function respondToOffer(Driver $driver, int $offerId, string $action): array
    {
        $offer = CourierJobOffer::where('driver_id', $driver->id)->findOrFail($offerId);

        if ($action === 'accept') {
            if ($offer->status !== 'offered') {
                return [
                    'success' => false,
                    'message' => 'Esta oferta ya no se encuentra disponible (estado: ' . $offer->status . ').',
                ];
            }

            if ($offer->expires_at && now()->gt($offer->expires_at)) {
                $offer->update(['status' => 'expired']);
                return [
                    'success' => false,
                    'message' => 'La oferta ha expirado (ventana de 15 segundos finalizada).',
                ];
            }

            // Verify driver economic eligibility
            $economicCheck = DriverEconomicPolicyService::canDriverReceiveOrders($driver);
            if (!$economicCheck['allowed']) {
                return [
                    'success' => false,
                    'message' => $economicCheck['reason'] ?? 'Recarga tu saldo para aceptar este pedido.',
                    'economic_status' => $economicCheck,
                ];
            }

            DB::transaction(function () use ($offer, $driver) {
                $offer->update([
                    'status'        => 'accepted',
                    'responded_at'  => now(),
                ]);

                $job = $offer->job;
                if ($job instanceof DeliveryOrder) {
                    $job->update([
                        'driver_id'          => $driver->id,
                        'status'             => 'on_way',
                        'driver_assigned_at' => now(),
                        'dispatch_timeout_at'=> null,
                    ]);
                    event(new DeliveryOrderStatusUpdated($job->fresh('store', 'user')));
                } elseif ($job instanceof Favor) {
                    $job->update([
                        'courier_id'          => $driver->id,
                        'status'              => 'accepted',
                        'courier_assigned_at' => now(),
                        'dispatch_timeout_at' => null,
                    ]);
                    event(new FavorStatusUpdated($job->fresh()));
                } elseif ($job instanceof CourierBatch) {
                    $job->update([
                        'driver_id'   => $driver->id,
                        'status'      => CourierBatch::STATUS_ACCEPTED,
                        'accepted_at' => now(),
                    ]);
                    foreach ($job->batchOrders as $bo) {
                        if ($bo->order instanceof DeliveryOrder) {
                            $bo->order->update(['driver_id' => $driver->id, 'status' => 'on_way']);
                        } elseif ($bo->order instanceof Favor) {
                            $bo->order->update(['courier_id' => $driver->id, 'status' => 'accepted']);
                        }
                    }
                }
            });

            return [
                'success' => true,
                'message' => '¡Oferta aceptada con éxito!',
                'job'     => $offer->fresh(['job'])->job,
            ];
        }

        // Action is 'reject'
        $offer->update([
            'status'       => 'rejected',
            'responded_at' => now(),
        ]);

        // Immediately trigger cascade to next courier without waiting for timer
        $job = $offer->job;
        if ($job instanceof DeliveryOrder || $job instanceof Favor) {
            self::dispatchOrder($job);
        } elseif ($job instanceof CourierBatch) {
            self::dispatchBatch($job);
        }

        return [
            'success' => true,
            'message' => 'Oferta rechazada. El pedido ha sido reasignado.',
        ];
    }

    /**
     * Scan and expire open offers whose 15-second response window has elapsed.
     */
    public static function processExpiredOffers(): int
    {
        $expiredOffers = CourierJobOffer::where('status', 'offered')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expiredOffers as $offer) {
            $offer->update(['status' => 'expired']);
            if ($offer->driver) {
                event(new CourierOfferExpired($offer->driver, $offer));
            }

            // Re-dispatch job/batch to next eligible courier
            $job = $offer->job;
            if ($job instanceof DeliveryOrder || $job instanceof Favor) {
                if ($job->status === 'searching_courier' || is_null($job->driver_id ?? $job->courier_id)) {
                    self::dispatchOrder($job);
                }
            } elseif ($job instanceof CourierBatch) {
                if ($job->status === CourierBatch::STATUS_OFFERED && is_null($job->driver_id)) {
                    self::dispatchBatch($job);
                }
            }
            $count++;
        }

        return $count;
    }

    /**
     * Find next eligible online courier nearest to the coordinates.
     * Enforces Lizto Economic Policy: Repartidores sin saldo son excluidos.
     */
    public static function findNextEligibleDriver(float $lat, float $lng, array $excludeDriverIds = []): ?Driver
    {
        $radiusKm = (float) (gs('delivery_coverage_radius') ?? 10.0);

        $onlineDrivers = Driver::where('status', Status::ENABLE)
            ->where(function ($q) {
                $q->where('is_online', 1)->orWhere('online_status', 1);
            })
            ->when(!empty($excludeDriverIds), fn ($q) => $q->whereNotIn('id', $excludeDriverIds))
            ->get();

        $eligibleCouriers = [];
        foreach ($onlineDrivers as $driver) {
            // Lizto Economic Policy Check
            $check = DriverEconomicPolicyService::canDriverReceiveOrders($driver);
            if (!$check['allowed']) {
                continue; // Insufficient balance or blocked -> exclude from dispatch
            }

            $driverLat = (float) ($driver->latitude ?? 0);
            $driverLng = (float) ($driver->longitude ?? 0);

            if ($driverLat != 0 && $driverLng != 0) {
                $dist = DeliveryPricing::distanceKm($lat, $lng, $driverLat, $driverLng);
                if ($dist <= $radiusKm) {
                    $eligibleCouriers[] = [
                        'driver'   => $driver,
                        'distance' => $dist,
                    ];
                }
            } else {
                $eligibleCouriers[] = [
                    'driver'   => $driver,
                    'distance' => 999.0,
                ];
            }
        }

        if (empty($eligibleCouriers)) {
            return null;
        }

        usort($eligibleCouriers, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return $eligibleCouriers[0]['driver'];
    }

    /**
     * Determine whether the driver qualifies for automatic acceptance.
     */
    public static function shouldAutoAccept(Driver $driver, float $earning, float $distanceKm): bool
    {
        if (!$driver->auto_accept_enabled) {
            return false;
        }

        $minEarning = (float) ($driver->auto_accept_min_earning ?? 0.0);
        $maxDistance = (float) ($driver->auto_accept_max_distance ?? 999.0);

        if ($earning < $minEarning) {
            return false;
        }

        if ($maxDistance > 0 && $distanceKm > $maxDistance) {
            return false;
        }

        return true;
    }

    private static function executeAutoAcceptance(Driver $driver, $order, array $fare, array $normalized): array
    {
        DB::transaction(function () use ($driver, $order) {
            if ($order instanceof DeliveryOrder) {
                $order->update([
                    'driver_id'          => $driver->id,
                    'status'             => 'on_way',
                    'driver_assigned_at' => now(),
                    'dispatch_timeout_at'=> null,
                ]);
                event(new DeliveryOrderStatusUpdated($order->fresh('store', 'user')));
            } elseif ($order instanceof Favor) {
                $order->update([
                    'courier_id'          => $driver->id,
                    'status'              => 'accepted',
                    'courier_assigned_at' => now(),
                    'dispatch_timeout_at' => null,
                ]);
                event(new FavorStatusUpdated($order->fresh()));
            }
        });

        $offer = CourierOfferTracker::offered($driver, $order, 'auto_accepted', true, null);
        $offer->update(['status' => 'accepted', 'responded_at' => now()]);

        // Notify driver
        FcmService::sendToDriver(
            $driver,
            '⚡ ¡Pedido auto-aceptado!',
            "Se ha auto-aceptado el pedido #{$normalized['order_no']} por S/ " . number_format($fare['total_payout'], 2),
            [
                'type'         => 'auto_accepted',
                'order_id'     => (string) $order->id,
                'total_payout' => (string) $fare['total_payout'],
            ]
        );

        event(new CourierOfferAutoAccepted($driver, $offer, [
            'order_id'       => $order->id,
            'order_no'       => $normalized['order_no'],
            'driver_earning' => $fare['driver_earning'],
            'total_payout'   => $fare['total_payout'],
            'points'         => $fare['points'],
            'fare_breakdown' => $fare,
        ]));

        return [
            'status'         => 'auto_accepted',
            'auto_accepted'  => true,
            'offered'        => true,
            'driver'         => $driver,
            'offer_id'       => $offer->id,
            'fare_breakdown' => $fare,
        ];
    }

    private static function executeBatchAutoAcceptance(Driver $driver, CourierBatch $batch): array
    {
        DB::transaction(function () use ($driver, $batch) {
            $batch->update([
                'driver_id'   => $driver->id,
                'status'      => CourierBatch::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            foreach ($batch->batchOrders as $bo) {
                if ($bo->order instanceof DeliveryOrder) {
                    $bo->order->update(['driver_id' => $driver->id, 'status' => 'on_way']);
                } elseif ($bo->order instanceof Favor) {
                    $bo->order->update(['courier_id' => $driver->id, 'status' => 'accepted']);
                }
            }
        });

        $offer = CourierOfferTracker::offered($driver, $batch, 'auto_accepted_batch', true, null);
        $offer->update(['status' => 'accepted', 'responded_at' => now()]);

        FcmService::sendToDriver(
            $driver,
            "⚡ ¡Lote {$batch->batch_type} auto-aceptado!",
            "Se ha auto-aceptado el lote #{$batch->batch_no} por S/ " . number_format($batch->total_payout, 2),
            [
                'type'         => 'batch_auto_accepted',
                'batch_id'     => (string) $batch->id,
                'total_payout' => (string) $batch->total_payout,
            ]
        );

        event(new CourierOfferAutoAccepted($driver, $offer, [
            'batch_id'       => $batch->id,
            'batch_no'       => $batch->batch_no,
            'driver_earning' => $batch->driver_earning,
            'total_payout'   => $batch->total_payout,
            'points'         => $batch->total_points,
            'fare_breakdown' => $batch->fare_breakdown,
        ]));

        return [
            'status'        => 'auto_accepted',
            'auto_accepted' => true,
            'offered'       => true,
            'driver'        => $driver,
            'offer_id'      => $offer->id,
        ];
    }
}
