<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\CourierLocationUpdated;
use App\Events\DeliveryOrderStatusUpdated;
use App\Events\FavorStatusUpdated;
use App\Events\FavorMessageReceived;
use App\Events\JobMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\CourierEarning;
use App\Models\CourierProof;
use App\Models\DeliveryCommission;
use App\Models\DeliveryOrder;
use App\Models\DeviceToken;
use App\Models\Favor;
use App\Models\FavorMessage;
use App\Models\JobMessage;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\FcmService;
use App\Services\DeliveryFinancialLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CourierJobController extends Controller
{
    private function driver()
    {
        return auth()->user();
    }

    public function pendingJobs(Request $request)
    {
        $driver = $this->driver();

        // Verificar condiciones económicas centralizadas de Lizto
        $economicCheck = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
        if (!$economicCheck['allowed']) {
            return apiResponse('pending_jobs', 'success', [$economicCheck['reason']], [
                'jobs'            => [],
                'economic_status' => $economicCheck,
            ]);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = (float) ($request->radius ?? gs('delivery_coverage_radius') ?? 8);

        $deliveryQuery = DeliveryOrder::whereNull('driver_id')
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('store', 'user', 'items.variation', 'items.addons');

        $favorQuery = Favor::where('status', 'searching_courier')
            ->where(function ($query) use ($driver) {
                $query->whereNull('courier_id')
                    ->orWhere(function ($targeted) use ($driver) {
                        $targeted->where('courier_id', $driver->id)
                            ->whereIn('dispatch_mode', ['admin_targeted', 'seller_nearby', 'seller_expanded']);
                    });
            })
            ->with('user');

        if ($lat && $lng) {
            $deliveryHaversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
            $favorHaversine = "(6371 * acos(cos(radians(?)) * cos(radians(pickup_lat)) * cos(radians(pickup_lng) - radians(?)) + sin(radians(?)) * sin(radians(pickup_lat))))";

            $deliveryQuery->whereHas('store', function ($q) use ($deliveryHaversine, $lat, $lng, $radius) {
                $q->whereRaw("$deliveryHaversine <= ?", [(float) $lat, (float) $lng, (float) $lat, $radius]);
            });

            $favorQuery->whereNotNull('pickup_lat')->whereNotNull('pickup_lng')
                ->whereRaw("$favorHaversine <= ?", [(float) $lat, (float) $lng, (float) $lat, $radius]);
        }

        $deliveryJobs = $deliveryQuery->get()->map(fn($o) => $this->formatJob($o, 'delivery'));
        $favorJobs = $favorQuery->get()->map(fn($o) => $this->formatFavorJob($o));

        $jobs = $deliveryJobs->concat($favorJobs)->sortByDesc('created_at')->values();

        $batches = \App\Models\CourierBatch::where(function ($q) use ($driver) {
                $q->where('driver_id', $driver->id)
                  ->orWhereNull('driver_id');
            })
            ->whereIn('status', [\App\Models\CourierBatch::STATUS_PENDING, \App\Models\CourierBatch::STATUS_OFFERED])
            ->with('batchOrders.order')
            ->get()
            ->map(fn($b) => $this->formatBatch($b));

        return apiResponse('pending_jobs', 'success', ['Pedidos disponibles'], [
            'jobs'            => $jobs,
            'batches'         => $batches,
            'economic_status' => $economicCheck,
        ]);
    }

    public function activeJobs()
    {
        $driver = $this->driver();

        $deliveryJobs = DeliveryOrder::where('driver_id', $driver->id)
            ->whereIn('status', ['confirmed', 'preparing', 'ready', 'on_way'])
            ->with('store', 'user')
            ->get()
            ->map(fn($o) => $this->formatJob($o, 'delivery'));

        $favorJobs = Favor::where('courier_id', $driver->id)
            ->whereIn('status', ['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery'])
            ->with('user')
            ->get()
            ->map(fn($o) => $this->formatFavorJob($o));

        $jobs = $deliveryJobs->concat($favorJobs)->values();

        return apiResponse('active_jobs', 'success', ['Pedidos activos'], [
            'jobs' => $jobs,
        ]);
    }

    public function jobHistory(Request $request)
    {
        $driver = $this->driver();

        $deliveryJobs = DeliveryOrder::where('driver_id', $driver->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with('store', 'user')
            ->get()
            ->map(fn($o) => $this->formatJob($o, 'delivery'));

        $favorJobs = Favor::where('courier_id', $driver->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with('user')
            ->get()
            ->map(fn($o) => $this->formatFavorJob($o));

        $jobs = $deliveryJobs->concat($favorJobs)->sortByDesc(function ($j) {
            return $j['requested_at'] ?? $j['created_at'];
        })->values();

        return apiResponse('job_history', 'success', ['Historial de pedidos'], [
            'jobs' => $jobs,
        ]);
    }

    public function jobDetail(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type;

        if ($type === 'favor') {
            $job = Favor::where(function ($query) use ($driver) {
                    $query->where('courier_id', $driver->id)
                        ->orWhere(function ($available) use ($driver) {
                            $available->where('status', 'searching_courier')
                                ->where(function ($assignment) use ($driver) {
                                    $assignment->whereNull('courier_id')
                                        ->orWhere('courier_id', $driver->id);
                                });
                        });
                })
                ->with('user', 'messages')
                ->findOrFail($id);
            return apiResponse('job_detail', 'success', ['Detalle del pedido'], [
                'job' => $this->formatFavorJob($job),
            ]);
        }

        $job = DeliveryOrder::where(function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->orWhere(function ($available) {
                        $available->whereNull('driver_id')
                            ->whereIn('status', ['confirmed', 'preparing', 'ready']);
                    });
            })
            ->with('store', 'user', 'items.variation', 'items.addons')
            ->findOrFail($id);
        return apiResponse('job_detail', 'success', ['Detalle del pedido'], [
            'job' => $this->formatJob($job, 'delivery'),
        ]);
    }

    public function acceptJob(Request $request, $id)
    {
        $driver = $this->driver();
        $type   = $request->type ?? 'delivery';

        if ($blockedUntil = $this->cancellationBlockedUntil($driver->id)) {
            return apiResponse('courier_temporarily_suspended', 'error', [
                'No puedes aceptar pedidos hasta ' . $blockedUntil->format('H:i') . ' por cancelaciones recientes.',
            ], ['blocked_until' => $blockedUntil->toIso8601String()]);
        }

        // Verificar condiciones económicas centralizadas de Lizto
        $economicCheck = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
        if (!$economicCheck['allowed']) {
            return apiResponse('insufficient_balance', 'error', [
                $economicCheck['reason'] ?? 'Recarga tu wallet para aceptar pedidos.',
            ], [
                'economic_state'      => $economicCheck['economic_state'],
                'min_recharge'        => $economicCheck['min_recharge'],
                'promotional_balance' => $economicCheck['promotional_balance'],
                'recharge_balance'    => $economicCheck['recharge_balance'],
                'balance'             => $economicCheck['balance'],
            ]);
        }

        if ($type === 'favor') {
            $job = Favor::where('status', 'searching_courier')->findOrFail($id);

            // After the initial broadcast window, admin-created requests are
            // offered to one courier at a time. A stale notification must not
            // let another courier take the request during that response window.
            if (in_array($job->dispatch_mode, ['admin_targeted', 'seller_nearby', 'seller_expanded'], true) && (int) $job->courier_id !== (int) $driver->id) {
                return apiResponse('job_reserved_for_another_courier', 'error', ['Este envío está siendo consultado con otro repartidor.']);
            }

            // Admin-created favors (user_id = null): direct accept, no bidding

            if (is_null($job->user_id) || $job->source_type === 'seller') {
                $job->update([
                    'courier_id'         => $driver->id,
                    'status'             => 'accepted',
                    'courier_assigned_at' => now(),
                    'dispatch_timeout_at' => null,
                ]);
                \App\Services\CourierOfferTracker::respond($driver, $job, 'accepted');
                FcmService::sendToAllCouriers(
                    'Envío tomado',
                    'Un repartidor aceptó el envío #' . $job->order_no,
                    ['type' => 'favor_taken', 'favor_id' => (string) $job->id]
                );
                $job = $job->fresh(['courier', 'seller']);
                event(new FavorStatusUpdated($job));
                $this->sendStatusPush($job, 'favor', 'accepted');
                return apiResponse('job_accepted', 'success', ['Envío aceptado directamente'], [
                    'job' => $this->formatFavorJob($job),
                ]);
            }

            // User-created favors: bidding system
            $bid = $job->bids()->create([
                'courier_id' => $driver->id,
                'bid_amount' => $job->total ?? $job->delivery_fee + 10,
                'status'     => 'pending',
            ]);
            return apiResponse('bid_placed', 'success', ['Oferta enviada'], ['bid' => $bid]);
        }

        // The courier search starts as soon as the seller confirms the order.
        $job = DeliveryOrder::whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->whereNull('driver_id')->findOrFail($id);
        $job->update([
            'driver_id'          => $driver->id,
            'status'             => 'on_way',
            'driver_assigned_at' => now(),
        ]);
        \App\Services\CourierOfferTracker::respond($driver, $job, 'accepted');

        event(new DeliveryOrderStatusUpdated($job->fresh('store', 'user')));
        FcmService::sendToUser($job->user, 'Repartidor asignado', 'Tu pedido #' . $job->order_no . ' será entregado pronto', ['order_id' => (string) $job->id, 'order_no' => $job->order_no, 'type' => 'driver_assigned']);
        if ($job->store && $job->store->seller) {
            FcmService::sendToSeller($job->store->seller, 'Repartidor asignado', 'El repartidor ' . $driver->name . ' ha aceptado el pedido #' . $job->order_no, ['order_id' => (string) $job->id, 'order_no' => $job->order_no, 'type' => 'driver_assigned']);
        }

        return apiResponse('job_accepted', 'success', ['Pedido aceptado'], [
            'job' => $this->formatJob($job->fresh('store', 'user'), 'delivery'),
        ]);
    }

    public function rejectJob(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->input('type', 'delivery');
        $job = $type === 'favor'
            ? Favor::where('status', 'searching_courier')->findOrFail($id)
            : DeliveryOrder::where('status', 'ready')->findOrFail($id);

        \App\Services\CourierOfferTracker::respond($driver, $job, 'rejected');

        if ($type === 'favor' && (int) $job->courier_id === (int) $driver->id) {
            $mode = (string) $job->dispatch_mode;
            $job->update(['courier_id' => null, 'dispatch_timeout_at' => now()]);

            if (str_starts_with($mode, 'admin_')) {
                \App\Services\AdminDeliveryRequestDispatchService::targetNextCourier($job->fresh());
            } elseif (str_starts_with($mode, 'seller_')) {
                \App\Services\SellerFavorDispatchService::dispatchNext($job->fresh());
            }
        }

        return apiResponse('job_rejected', 'success', [
            'Solicitud rechazada correctamente. Esto no se considera una falta.',
        ]);
    }

    public function updateJobStatus(Request $request, $id)
    {
        $driver = $this->driver();
        $validator = Validator::make($request->all(), [
            'status'    => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'payment_confirmed' => 'nullable|boolean',
            'pin_code'  => 'nullable|string|max:4',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $type = $request->type ?? 'delivery';

        if ($type === 'favor') {
            $job = Favor::where('courier_id', $driver->id)->findOrFail($id);
        } else {
            $job = DeliveryOrder::where('driver_id', $driver->id)->findOrFail($id);
        }

        // Sprint 1: Enforce delivery confirmation (photo + PIN) before marking delivered
        if ($request->status === 'delivered') {
            $confirmationResult = \App\Services\DeliveryConfirmationService::attemptDeliver(
                $driver,
                $id,
                $type,
                $request->pin_code
            );

            if (!$confirmationResult['success']) {
                return apiResponse('delivery_confirmation_required', 'error', [
                    $confirmationResult['message'],
                ], [
                    'requirements' => $confirmationResult['requirements'],
                ]);
            }

            // Delivery was successful — skip the normal status update below
            // and go straight to the post-delivery logic
            $job->refresh();
        } else {
            // For non-delivered status changes, proceed normally
            if ($type === 'delivery' && $request->status === 'delivered' && !$job->payment_status && !$request->boolean('payment_confirmed')) {
                return apiResponse('payment_confirmation_required', 'error', ['Confirma que recibiste el pago antes de completar la entrega']);
            }

            $job->update(['status' => $request->status]);
        }

        if ($request->status === 'delivered') {
            $job->update(['delivered_at' => now()]);
            $paymentChannel = DeliveryFinancialLedger::paymentChannel($job);

            // Marcar como pagado automáticamente
            if ($type === 'delivery') {
                $job->update(['payment_status' => 1]);
            }

            $commission = DeliveryCommission::where('status', 1)->first();
            if ($type === 'favor') {
                $deliveryFee = $job->total ?? 0;
            } else {
                $deliveryFee = $job->delivery_fee ?? 0;
            }
            $earningIdentity = [
                'courier_id' => $driver->id,
                'job_type' => $type === 'favor' ? Favor::class : DeliveryOrder::class,
                'job_id' => $job->id,
            ];
            $existingEarning = CourierEarning::where($earningIdentity)->first();
            $commissionQuote = DeliveryFinancialLedger::driverCommissionQuote(
                $driver,
                (float) $deliveryFee,
                $type,
                $commission,
                !$existingEarning
            );
            $commissionAmount = $existingEarning
                ? (float) $existingEarning->commission
                : $commissionQuote['amount'];

            // Update earning record
            $courierEarning = CourierEarning::firstOrCreate($earningIdentity, [
                // La comisión se descuenta de la recarga/wallet, no de la ganancia generada.
                'amount'      => $deliveryFee,
                'commission'  => $commissionAmount,
                'commission_tier' => $commissionQuote['tier_name'],
                'commission_base_percent' => $commissionQuote['base_percent'],
                'commission_effective_percent' => $commissionQuote['effective_percent'],
                'commission_minimum' => $commissionQuote['minimum'],
                'completed_jobs_snapshot' => $commissionQuote['total_completed_jobs'],
                'description' => $type === 'favor' ? 'Entrega de favor' : 'Entrega de pedido #' . $job->order_no,
            ]);

            if ($courierEarning->wasRecentlyCreated) {
                DeliveryFinancialLedger::recordDriverEarning($driver, (float) $deliveryFee, $courierEarning);
            }

            if ($paymentChannel === 'cash') {
                DeliveryFinancialLedger::recordCashCollection($driver, (float) ($job->total ?? 0), $job);
            }

            // Política Económica de Lizto:
            // Mientras el saldo promocional esté disponible, las carreras son 100% para el repartidor (sin comisión tradicional).
            if ($courierEarning->wasRecentlyCreated) {
                $economicCheck = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
                if ($economicCheck['balance_type'] === 'promotional') {
                    // Repartidor en período promocional: conserva el 100% de la ganancia
                    $job->update(['commission_amount' => 0]);
                } else {
                    \App\Services\DriverEconomicPolicyService::consumeBalance(
                        $driver,
                        $commissionAmount,
                        'Servicio de entrega #' . ($type === 'favor' ? 'Favor-' . $job->id : $job->order_no),
                        $job
                    );
                }
            }

            // Seller receivable: only funds held by Lizto or its courier.
            if ($type === 'delivery' && $job->store?->seller) {
                $seller = $job->store->seller;
                $subtotal = $job->subtotal ?? 0;

                $storeCommissionType = $commission?->store_commission_type ?? 'percent';
                if ($storeCommissionType === 'percent') {
                    $storePercent = $commission?->store_commission_percent ?? 5;
                    $sellerCommission = $subtotal * $storePercent / 100;
                } else {
                    $sellerCommission = $commission?->store_fixed_amount ?? 0;
                }

                $sellerNet = max(0, $subtotal - $sellerCommission);

                DeliveryFinancialLedger::recordSellerReceivable($seller, $sellerNet, $paymentChannel, $job);
            }
        }

        if ($type === 'favor') {
            event(new FavorStatusUpdated($job));
        } else {
            event(new DeliveryOrderStatusUpdated($job));
        }

        if ($request->latitude && $request->longitude) {
            event(new CourierLocationUpdated(
                $type === 'favor' ? $id : 0,
                $request->latitude,
                $request->longitude,
                null,
                $type === 'delivery' ? $id : null,
                $job->user_id
            ));

            // Sprint 1: Broadcast ETA update with location
            if ($type === 'favor' && $job instanceof Favor) {
                try {
                    \App\Services\EtaService::broadcastFavorEta($job);
                } catch (\Throwable $e) {}
            }
        }

        // FCM push to user on status change
        $this->sendStatusPush($job, $type, $request->status);

        return apiResponse('status_updated', 'success', ['Estado actualizado'], [
            'job'            => $type === 'favor' ? $this->formatFavorJob($job->fresh()) : $this->formatJob($job->fresh('store', 'user'), 'delivery'),
            'balance'        => (float) $driver->fresh()->balance,
            'wallet_balance' => (float) $driver->fresh()->wallet->balance,
        ]);
    }

    public function cancelJob(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type ?? 'delivery';

        $validator = Validator::make($request->all(), [
            'reason_code' => 'required|in:vehicle_issue,route_or_distance,store_delay,personal_emergency,other',
            'reason_detail' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        if ($type === 'favor') {
            $job = Favor::where('courier_id', $driver->id)
                ->whereIn('status', ['accepted', 'on_way_to_pickup'])
                ->findOrFail($id);
            $job->update([
                'courier_id' => null,
                'status' => 'searching_courier',
                'courier_assigned_at' => null,
                'cancel_reason' => 'Cancelado por repartidor: ' . $request->reason_code,
            ]);
            $penalty = $this->registerCancellation($driver->id, $job->id, 'favor', $request->reason_code, $request->reason_detail);
            event(new FavorStatusUpdated($job->fresh()));
            $this->sendFavorSellerPush(
                $job->fresh('seller'),
                'Buscando otro repartidor',
                'El repartidor liberó la solicitud #' . $job->order_no . '. Ya estamos buscando un reemplazo.',
                'searching_courier'
            );
            FcmService::sendToAllCouriers('Nuevo favor disponible', 'Un repartidor canceló. El favor está disponible nuevamente.', ['job_id' => (string) $job->id, 'job_type' => 'favor']);
        } else {
            $job = DeliveryOrder::where('driver_id', $driver->id)
                ->whereIn('status', ['on_way', 'accepted', 'on_way_to_pickup'])
                ->with('store')->findOrFail($id);
            $storeName = $job->store?->name ?? 'tu zona';
            $job->update([
                'driver_id' => null,
                'status' => 'ready',
                'driver_assigned_at' => null,
                'cancel_reason' => 'Cancelado por repartidor: ' . $request->reason_code,
            ]);
            $penalty = $this->registerCancellation($driver->id, $job->id, 'delivery', $request->reason_code, $request->reason_detail);
            event(new DeliveryOrderStatusUpdated($job->fresh('store', 'user')));
            FcmService::sendToAllCouriers('Pedido disponible nuevamente', 'Un repartidor canceló. Hay un pedido listo en ' . $storeName, ['job_id' => (string) $job->id, 'order_no' => $job->order_no, 'job_type' => 'delivery']);
        }

        return apiResponse('job_cancelled', 'success', [
            'Pedido liberado. Otros repartidores ya pueden verlo. ' . $penalty['message'],
        ], $penalty);
    }

    private function cancellationBlockedUntil(int $courierId): ?\Carbon\Carbon
    {
        $blockedUntil = DB::table('courier_cancellations')
            ->where('courier_id', $courierId)
            ->where('blocked_until', '>', now())
            ->max('blocked_until');

        return $blockedUntil ? \Carbon\Carbon::parse($blockedUntil) : null;
    }

    private function registerCancellation(int $courierId, int $jobId, string $jobType, string $reasonCode, ?string $reasonDetail): array
    {
        $cancellationsInWeek = DB::table('courier_cancellations')
            ->where('courier_id', $courierId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count() + 1;

        $penaltyMinutes = match (true) {
            $cancellationsInWeek === 1 => 30,
            $cancellationsInWeek === 2 => 120,
            default => 1440,
        };
        $blockedUntil = now()->addMinutes($penaltyMinutes);

        DB::table('courier_cancellations')->insert([
            'courier_id' => $courierId,
            'job_id' => $jobId,
            'job_type' => $jobType,
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail,
            'penalty_minutes' => $penaltyMinutes,
            'blocked_until' => $blockedUntil,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = match ($penaltyMinutes) {
            30 => 'Perderás prioridad para recibir pedidos durante 30 minutos.',
            120 => 'Perderás prioridad para recibir pedidos durante 2 horas.',
            default => 'Tu cuenta no podrá aceptar pedidos durante 24 horas.',
        };

        return [
            'cancellations_in_week' => $cancellationsInWeek,
            'penalty_minutes' => $penaltyMinutes,
            'blocked_until' => $blockedUntil->toIso8601String(),
            'message' => $message,
        ];
    }

    public function sendLocation(Request $request, $id)
    {
        $driver = $this->driver();

        // Persist GPS to drivers table so sellers can read it
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $driver->update([
                'current_lat'           => $request->latitude,
                'current_lot'           => $request->longitude,
                'last_location_fetch_at' => now(),
            ]);
        }

        $job = DeliveryOrder::where('driver_id', $driver->id)->find($id);
        $favorId = null;
        $orderId = null;
        $userId = null;

        if (!$job) {
            $favor   = Favor::where('courier_id', $driver->id)->findOrFail($id);
            $favorId = $favor->id;
            $userId  = $favor->user_id;
        } else {
            $orderId = $job->id;
            $userId  = $job->user_id;
        }

        event(new CourierLocationUpdated(
            $favorId ?? 0, $request->latitude, $request->longitude, $request->bearing, $orderId, $userId
        ));

        // WebSocket nativo: publicar la posición en vivo del repartidor.
        \App\Services\RealtimePublisher::driverLocation(
            (int) $driver->id,
            (float) $request->latitude,
            (float) $request->longitude,
            $request->filled('bearing') ? (float) $request->bearing : null,
            $request->filled('speed') ? (float) $request->speed : null,
        );

        // Sprint 1: Broadcast ETA update with location
        if ($favorId) {
            try {
                $favor = Favor::find($favorId);
                if ($favor) {
                    \App\Services\EtaService::broadcastFavorEta($favor);
                }
            } catch (\Throwable $e) {}
        }

        return apiResponse('location_sent', 'success', ['Ubicación actualizada']);
    }

    public function uploadProof(Request $request, $id)
    {
        $driver = $this->driver();

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:5120',
            'note'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $job = DeliveryOrder::where('driver_id', $driver->id)->find($id);
        $type = 'delivery';
        if (!$job) {
            Favor::where('courier_id', $driver->id)->findOrFail($id);
            $type = 'favor';
        }

        try {
            $imagePath = fileUploader($request->image, getFilePath('proof'));
        } catch (\Exception $e) {
            return apiResponse('upload_error', 'error', [$e->getMessage()]);
        }

        CourierProof::create([
            'job_id'     => $id,
            'job_type'   => $type,
            'courier_id' => $driver->id,
            'image'      => $imagePath,
            'note'       => $request->note,
        ]);

        return apiResponse('proof_uploaded', 'success', ['Comprobante subido']);
    }

    // ── Sprint 1: Delivery Confirmation ──

    public function verifyPin(Request $request, $id)
    {
        $driver = $this->driver();

        $validator = Validator::make($request->all(), [
            'pin_code' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $type = $request->type ?? 'favor';

        $verified = \App\Services\DeliveryConfirmationService::verifyPin(
            $driver,
            $id,
            $type,
            $request->pin_code
        );

        if ($verified) {
            return apiResponse('pin_verified', 'success', ['PIN verificado correctamente']);
        }

        return apiResponse('pin_invalid', 'error', ['El código PIN es incorrecto']);
    }

    public function getConfirmationRequirements(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type ?? 'favor';

        $job = $type === 'favor'
            ? Favor::where('courier_id', $driver->id)->findOrFail($id)
            : DeliveryOrder::where('driver_id', $driver->id)->findOrFail($id);

        $requirements = \App\Services\DeliveryConfirmationService::getRequirements($job, $type);

        return apiResponse('confirmation_requirements', 'success', ['Requisitos de entrega'], [
            'requirements' => $requirements,
        ]);
    }

    // ── Sprint 3.2: Return Handling ──

    public function acceptReturn(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type ?? 'favor';

        if ($type !== 'favor') {
            return apiResponse('error', 'error', ['Las devoluciones solo aplican para favores/envíos.']);
        }

        $favor = Favor::where('return_status', 'return_requested')->findOrFail($id);

        $result = \App\Services\ReturnHandlingService::acceptReturn($driver, $favor);

        if ($result['success']) {
            return apiResponse('return_accepted', 'success', [$result['message']]);
        }

        return apiResponse('error', 'error', [$result['message']]);
    }

    public function pickupReturn(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type ?? 'favor';

        if ($type !== 'favor') {
            return apiResponse('error', 'error', ['Las devoluciones solo aplican para favores/envíos.']);
        }

        $favor = Favor::where('courier_id', $driver->id)
            ->whereIn('return_status', ['return_requested', 'return_assigned'])
            ->findOrFail($id);

        $result = \App\Services\ReturnHandlingService::pickupReturn($favor);

        if ($result['success']) {
            return apiResponse('return_picked_up', 'success', [$result['message']]);
        }

        return apiResponse('error', 'error', [$result['message']]);
    }

    public function completeReturn(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type ?? 'favor';

        if ($type !== 'favor') {
            return apiResponse('error', 'error', ['Las devoluciones solo aplican para favores/envíos.']);
        }

        $favor = Favor::where('courier_id', $driver->id)
            ->where('return_status', 'return_in_transit')
            ->findOrFail($id);

        $result = \App\Services\ReturnHandlingService::completeReturn($favor);

        if ($result['success']) {
            return apiResponse('return_completed', 'success', [$result['message']]);
        }

        return apiResponse('error', 'error', [$result['message']]);
    }

    // ── Messages (Chat) ──

    public function messages($jobId)
    {
        $driver = $this->driver();
        $job = $this->ensureJobAccess($driver, $jobId);

        // Favors have one shared conversation for the customer apps and the
        // courier app. Regular delivery orders keep their JobMessage thread.
        if ($job instanceof Favor) {
            $msgs = $job->messages()->orderBy('id', 'desc')->paginate(50);
            return apiResponse('favor_messages', 'success', ['Mensajes'], [
                'messages' => $msgs,
            ]);
        }

        $msgs = JobMessage::where('job_id', $jobId)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return apiResponse('job_messages', 'success', ['Mensajes'], [
            'messages' => $msgs,
        ]);
    }

    public function sendMessage(Request $request, $jobId)
    {
        $driver = $this->driver();
        $job = $this->ensureJobAccess($driver, $jobId);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        if ($job instanceof Favor) {
            $msg = $job->messages()->create([
                'sender_id'   => $driver->id,
                'sender_type' => \App\Models\Driver::class,
                'sender_name' => trim($driver->firstname . ' ' . $driver->lastname),
                'sender_role' => 'courier',
                'message'     => $request->message,
            ]);

            broadcast(new FavorMessageReceived($msg))->toOthers();

            return apiResponse('message_sent', 'success', ['Mensaje enviado'], [
                'message' => $msg,
            ]);
        }

        $msg = JobMessage::create([
            'job_id'      => $jobId,
            'job_type'    => get_class($job),
            'sender_id'   => $driver->id,
            'sender_type' => \App\Models\Driver::class,
            'sender_name' => $driver->firstname . ' ' . $driver->lastname,
            'sender_role' => 'courier',
            'message'     => $request->message,
        ]);

        broadcast(new JobMessageReceived($msg))->toOthers();

        return apiResponse('message_sent', 'success', ['Mensaje enviado'], [
            'message' => $msg,
        ]);
    }

    public function sendImage(Request $request, $jobId)
    {
        $driver = $this->driver();
        $job = $this->ensureJobAccess($driver, $jobId);

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = fileUploader($request->image, getFilePath('chat'));
            } catch (\Exception $e) {
                return apiResponse('upload_error', 'error', [$e->getMessage()]);
            }
        }

        if ($job instanceof Favor) {
            $msg = $job->messages()->create([
                'sender_id'   => $driver->id,
                'sender_type' => \App\Models\Driver::class,
                'sender_name' => trim($driver->firstname . ' ' . $driver->lastname),
                'sender_role' => 'courier',
                'image'       => $imagePath,
            ]);

            broadcast(new FavorMessageReceived($msg))->toOthers();

            return apiResponse('message_sent', 'success', ['Imagen enviada'], [
                'message' => $msg,
            ]);
        }

        $msg = JobMessage::create([
            'job_id'      => $jobId,
            'job_type'    => get_class($job),
            'sender_id'   => $driver->id,
            'sender_type' => \App\Models\Driver::class,
            'sender_name' => $driver->firstname . ' ' . $driver->lastname,
            'sender_role' => 'courier',
            'image'       => $imagePath,
        ]);

        broadcast(new JobMessageReceived($msg))->toOthers();

        return apiResponse('message_sent', 'success', ['Imagen enviada'], [
            'message' => $msg,
        ]);
    }

    private function ensureJobAccess($driver, $jobId)
    {
        $order = DeliveryOrder::where('driver_id', $driver->id)->find($jobId);
        if ($order) return $order;

        $favor = Favor::where('courier_id', $driver->id)->find($jobId);
        if ($favor) return $favor;

        abort(403, 'No tienes acceso a este trabajo');
    }

    // ── Earnings ──

    public function earnings()
    {
        $driver = $this->driver();

        $today    = CourierEarning::where('courier_id', $driver->id)->whereDate('created_at', today())->sum('amount');
        $week     = CourierEarning::where('courier_id', $driver->id)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount');
        $month    = CourierEarning::where('courier_id', $driver->id)->whereMonth('created_at', now()->month)->sum('amount');
        $total    = CourierEarning::where('courier_id', $driver->id)->sum('amount');

        // Real earning balance (after settlements/payments by admin)
        $earningBalance = (float) ($driver->earning_balance ?? 0);

        // Total amount already paid/settled to the driver
        $totalPaid = \DB::table('driver_earning_transactions')
            ->where('driver_id', $driver->id)
            ->where('trx_type', '-')
            ->whereIn('type', ['settlement', 'payment'])
            ->sum('amount');

        // Last 5 settlement/payment transactions
        $recentSettlements = \DB::table('driver_earning_transactions')
            ->where('driver_id', $driver->id)
            ->whereIn('type', ['settlement', 'payment', 'adjustment'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'type', 'trx_type', 'amount', 'post_balance', 'settlement_method', 'notes', 'created_at'])
            ->map(function ($r) {
                return [
                    'id'                 => $r->id,
                    'type'               => $r->type,
                    'trx_type'           => $r->trx_type,
                    'amount'             => (float) $r->amount,
                    'post_balance'       => (float) $r->post_balance,
                    'settlement_method'  => $r->settlement_method,
                    'notes'              => $r->notes,
                    'date'               => $r->created_at,
                ];
            })->values();

        $commission = DeliveryCommission::where('status', 1)->first();
        $baseCommissionPercent = (float) ($commission?->delivery_percent ?? 10);
        $tierInfo = DeliveryFinancialLedger::getDriverDynamicCommissionPercent($driver->id, $baseCommissionPercent);
        $offerMetrics = DeliveryFinancialLedger::driverOfferMetrics($driver->id);

        return apiResponse('earnings', 'success', ['Ganancias'], [
            'today_earnings'       => $today,
            'week_earnings'        => $week,
            'month_earnings'       => $month,
            'total_earnings'       => $total,        // Historial bruto acumulado
            'earning_balance'      => $earningBalance, // Saldo real pendiente de cobro
            'total_paid'           => (float) $totalPaid,   // Total ya pagado por admin
            'today_jobs'           => CourierEarning::where('courier_id', $driver->id)->whereDate('created_at', today())->count(),
            'week_jobs'            => CourierEarning::where('courier_id', $driver->id)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month_jobs'           => CourierEarning::where('courier_id', $driver->id)->whereMonth('created_at', now()->month)->count(),
            'total_jobs'           => CourierEarning::where('courier_id', $driver->id)->count(),
            'avg_rating'           => \App\Models\DeliveryReview::where('courier_id', $driver->id)->avg('rating') ?? 0,
            'recent_settlements'   => $recentSettlements,
            'tier_name'            => $tierInfo['tier_name'],
            'tier_badge'           => $tierInfo['tier_badge'],
            'effective_percent'    => $tierInfo['effective_percent'],
            'base_commission_percent' => $baseCommissionPercent,
            'minimum_commission'   => (float) ($commission?->min_commission ?? 1),
            'total_weekly_jobs'    => $tierInfo['total_weekly_jobs'],
            'total_completed_jobs' => $tierInfo['total_completed_jobs'],
            'next_tier_needed'     => $tierInfo['next_tier_needed'],
            'next_tier_name'       => $tierInfo['next_tier_name'],
            'offer_metrics'        => $offerMetrics,
        ]);
    }

    // ── Helpers ──

    private function formatJob($order, $type)
    {
        $paymentName = trim((string) ($order->payment_method_name ?: 'Efectivo'));
        $normalizedPayment = \Illuminate\Support\Str::lower($paymentName);
        $wallet = str_contains($normalizedPayment, 'yape')
            ? 'yape'
            : (str_contains($normalizedPayment, 'plin') ? 'plin' : null);
        $qrString = $wallet === 'yape'
            ? ($order->store?->yape_qr_string ?: $order->store?->qr_code)
            : ($wallet === 'plin' ? ($order->store?->plin_qr_string ?: $order->store?->qr_code) : $order->store?->qr_code);

        if (!$wallet && $qrString) {
            $wallet = 'qr';
        }

        $distKm = null;
        if ($order->store && $order->store->latitude && $order->delivery_lat) {
            $distKm = round(\App\Support\DeliveryPricing::distanceKm(
                (float) $order->store->latitude, (float) $order->store->longitude,
                (float) $order->delivery_lat, (float) $order->delivery_lng
            ), 2);
        }
        $durMin = $distKm ? \App\Services\RouteOptimizationService::estimateTimeMinutes($distKm) : 10.0;
        $fareBreakdown = \App\Services\DriverFareEngine::calculateRouteFare(
            $distKm ?? 2.0,
            $durMin,
            1,
            \App\Services\DemandEngine::TIER_NORMAL,
            (float) ($order->tip ?? 0)
        );

        return [
            'id'              => $order->id,
            'type'            => $type,
            'order_no'        => $order->order_no,
            'customer_name'   => $order->contact_name ?: $order->user?->fullname,
            'customer_phone'  => $order->contact_phone ?: $order->user?->mobile,
            'pickup_address'  => $order->store?->address,
            'pickup_lat'      => $order->store?->latitude,
            'pickup_lng'      => $order->store?->longitude,
            'delivery_address'=> $order->delivery_address,
            'delivery_lat'    => $order->delivery_lat,
            'delivery_lng'    => $order->delivery_lng,
            'distance_km'     => $distKm,
            'duration_minutes'=> $durMin,
            'amount'          => $order->total,
            'subtotal'        => $order->subtotal,
            'delivery_fee'    => $order->delivery_fee,
            'tip'             => $order->tip,
            'total_earning'   => $fareBreakdown['total_payout'],
            'driver_earning'  => $fareBreakdown['driver_earning'],
            'total_payout'    => $fareBreakdown['total_payout'],
            'points'          => $fareBreakdown['points'],
            'batch_id'        => $order->courier_batch_id,
            'fare_breakdown'  => $fareBreakdown,
            'status'          => $order->status,
            'store_name'      => $order->store?->name,
            'description'     => $order->notes,
            'items'           => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'variation' => $item->variation ? [
                    'name' => $item->variation->variation_name,
                    'price' => (float) $item->variation->variation_price,
                ] : null,
                'addons' => $item->addons->map(fn ($addon) => [
                    'name' => $addon->addon_name,
                    'price' => (float) $addon->addon_price,
                ])->values(),
            ])->values(),
            'payment_method_code' => (string) ($order->payment_method_code ?? '0'),
            'payment_method_name' => $paymentName,
            'payment_status'  => (int) $order->payment_status,
            'requires_payment_collection' => !(bool) $order->payment_status,
            'payment_wallet'  => $wallet,
            'payment_qr_string' => $qrString,
            'requested_at'    => optional($order->created_at)->toIso8601String(),
            'requested_at_text' => optional($order->created_at)->format('d/m/Y H:i'),
            'delivered_at'    => optional($order->delivered_at)->toIso8601String(),
            'delivered_at_text' => optional($order->delivered_at)->format('d/m/Y H:i'),
            'created_at'      => $order->created_at,
            'updated_at'      => $order->updated_at,
        ];
    }

    private function formatFavorJob($favor)
    {
        $baseDeliveryFee = (float) ($favor->delivery_fee ?? 0);
        $fee = (float) ($favor->total ?? $baseDeliveryFee);

        $distanceKm = null;
        if ($favor->pickup_lat && $favor->pickup_lng && $favor->delivery_lat && $favor->delivery_lng) {
            $distanceKm = round(\App\Support\DeliveryPricing::distanceKm(
                (float) $favor->pickup_lat, (float) $favor->pickup_lng,
                (float) $favor->delivery_lat, (float) $favor->delivery_lng
            ), 2);
        }

        $durMin = $distanceKm ? \App\Services\RouteOptimizationService::estimateTimeMinutes($distanceKm) : 10.0;
        $fareBreakdown = \App\Services\DriverFareEngine::calculateRouteFare(
            $distanceKm ?? 2.0,
            $durMin,
            1,
            \App\Services\DemandEngine::TIER_NORMAL,
            0.0
        );

        return [
            'id'              => $favor->id,
            'type'            => 'favor',
            'order_no'        => $favor->order_no,
            'customer_name'   => $favor->recipient_name ?? $favor->user?->fullname ?? 'Cliente',
            'customer_phone'  => $favor->recipient_phone ?? $favor->user?->mobile,
            'pickup_address'  => $favor->pickup_address,
            'pickup_lat'      => $favor->pickup_lat,
            'pickup_lng'      => $favor->pickup_lng,
            'delivery_address'=> $favor->delivery_address,
            'delivery_lat'    => $favor->delivery_lat,
            'delivery_lng'    => $favor->delivery_lng,
            'stops'           => $favor->stops,
            'distance_km'     => $distanceKm,
            'duration_minutes'=> $durMin,
            'amount'          => $favor->estimated_amount,
            'delivery_fee'    => $fee,
            'base_delivery_fee' => $baseDeliveryFee,
            'additional_charge' => (float) ($favor->estimated_amount ?? 0),
            'total_earning'   => $fareBreakdown['total_payout'],
            'driver_earning'  => $fareBreakdown['driver_earning'],
            'total_payout'    => $fareBreakdown['total_payout'],
            'points'          => $fareBreakdown['points'],
            'batch_id'        => $favor->courier_batch_id,
            'fare_breakdown'  => $fareBreakdown,
            'status'          => $favor->status,
            'store_name'      => $favor->store_name ?? $favor->seller?->name ?? 'Punto de recojo',
            'description'     => $favor->description,
            'payment_method_code'  => $favor->payment_method_code,
            'payment_method_name'  => $favor->payment_method_name,
            'payment_status'       => $favor->payment_status,
            'payer_type'           => $favor->payer_type,
            'cod_amount'           => $favor->cod_amount,
            'evidence_type'        => $favor->evidence_type,
            'requires_payment_collection' => $favor->payer_type === 'recipient',
            'is_short_distance'    => $isShortDistance,
            'short_distance_notice'=> $isShortDistance ? '⚡ Envío super corto (< 1 km): Tarifa plana S/ 4.00' : null,
            'requested_at'    => optional($favor->requested_at)->toIso8601String(),
            'requested_at_text' => optional($favor->requested_at)->format('d/m/Y H:i'),
            'delivered_at'    => optional($favor->delivered_at)->toIso8601String(),
            'delivered_at_text' => optional($favor->delivered_at)->format('d/m/Y H:i'),
            'created_at'      => $favor->created_at,
            'updated_at'      => $favor->updated_at,
            'favor'           => $favor->load('messages'),
        ];
    }

    private function calculateCommission($deliveryFee, $type = 'delivery')
    {
        $commissionConfig = DeliveryCommission::where('status', 1)->first();
        if (!$commissionConfig) {
            return ($type === 'favor' ? 15 : 10); // fallback defaults
        }

        if ($commissionConfig->courier_commission_type === 'fixed') {
            return (float) ($commissionConfig->courier_fixed_amount ?? 0);
        }

        $percent = $type === 'favor'
            ? (float) ($commissionConfig->favor_percent ?? 15)
            : (float) ($commissionConfig->delivery_percent ?? 10);

        $commission = $deliveryFee * $percent / 100;
        $min = (float) ($commissionConfig->min_commission ?? 1);

        return max($commission, $min);
    }

    private function ensureWallet($holder)
    {
        if (!$holder->wallet) {
            $wallet = Wallet::create(['holder_type' => get_class($holder), 'holder_id' => $holder->id]);
            $holder->update(['wallet_id' => $wallet->id]);
            $holder->setRelation('wallet', $wallet);
        } elseif (!$holder->wallet_id) {
            $holder->update(['wallet_id' => $holder->wallet->id]);
        }
        return $holder->wallet;
    }

    private function sendFcmToUser($order, $title, $body)
    {
        if (!$order->user) return;
        $orderNo = $order->order_no ?? '';
        $type = $order instanceof Favor ? 'favor' : 'delivery';
        FcmService::sendToUser($order->user, $title, $body, ['job_id' => (string) $order->id, 'order_no' => $orderNo, 'job_type' => $type]);
    }

    private function sendStatusPush($job, $type, $status)
    {
        $statusMessages = [
            'delivery' => [
                'on_way'       => ['Repartidor en camino', 'Tu pedido está en camino'],
                'on_way_to_pickup' => ['Repartidor en camino al pickup', 'El repartidor va rumbo a recoger tu pedido'],
                'at_pickup'    => ['Repartidor en el pickup', 'El repartidor está recogiendo tu pedido'],
                'on_way_to_delivery' => ['En camino a tu dirección', 'Tu pedido está en camino a tu domicilio'],
                'delivered'    => ['Pedido entregado', 'Tu pedido ha sido entregado'],
            ],
            'favor' => [
                'on_way_to_pickup' => ['Repartidor en camino', 'El repartidor va rumbo a recoger tu favor'],
                'at_pickup'        => ['Recogiendo tu favor', 'El repartidor está en el punto de recogida'],
                'on_way_to_delivery' => ['Tu favor en camino', 'Tu favor está en camino a la entrega'],
                'delivered'        => ['Favor entregado', 'Tu favor ha sido entregado'],
                'accepted'         => ['Repartidor asignado', 'Un repartidor ha aceptado tu favor'],
            ],
        ];

        $messages = $statusMessages[$type][$status] ?? null;
        if (!$messages) return;

        $data = [
            'job_id'   => (string) $job->id,
            'order_no' => $job->order_no ?? '',
            'job_type' => $type,
            'status'   => $status,
        ];

        if ($type === 'favor') {
            if ($job->user) {
                FcmService::sendToUser($job->user, $messages[0], $messages[1], $data);
            }
            $this->sendFavorSellerPush($job, $messages[0], $messages[1], $status);
        } else {
            if (!$job->user) return;
            FcmService::sendToUser($job->user, $messages[0], $messages[1], $data);
            if ($job->store && $job->store->seller) {
                $sellerData = array_merge($data, ['order_id' => (string) $job->id]);
                FcmService::sendToSeller($job->store->seller, $messages[0], 'Pedido #' . $job->order_no . ': ' . $messages[1], $sellerData);
            }
        }
    }

    private function sendFavorSellerPush($job, string $title, string $body, string $status): void
    {
        $seller = $job->relationLoaded('seller') ? $job->seller : $job->seller()->first();
        if (!$seller) return;

        FcmService::sendToSeller($seller, $title, 'Solicitud #' . $job->order_no . ': ' . $body, [
            'type' => 'favor_status_updated',
            'job_type' => 'favor',
            'favor_id' => (string) $job->id,
            'job_id' => (string) $job->id,
            'order_no' => $job->order_no ?? '',
            'status' => $status,
        ]);
    }

    public function walletTransactions()
    {
        $driver = $this->driver();
        $this->ensureWallet($driver);
        $summary = \App\Services\DriverEconomicPolicyService::getEconomicSummary($driver);

        return apiResponse('wallet_transactions', 'success', ['Historial de transacciones'], [
            'wallet_balance'      => (float) $driver->wallet->balance,
            'promotional_balance' => (float) ($driver->wallet->promotional_balance ?? 0),
            'recharge_balance'    => (float) ($driver->wallet->recharge_balance ?? 0),
            'economic_state'      => $summary['economic_state'],
            'can_receive_orders'  => $summary['allowed'],
            'min_recharge'        => $summary['min_recharge'],
            'reason'              => $summary['reason'],
            'transactions'        => $summary['recent_transactions'],
        ]);
    }

    public function economicStatus()
    {
        $driver = $this->driver();
        $summary = \App\Services\DriverEconomicPolicyService::getEconomicSummary($driver);

        return apiResponse('driver_economic_status', 'success', ['Estado económico del repartidor'], $summary);
    }

    public function heatmapData(Request $request)
    {
        // 1. Fetch Store Orders (Origin: Store location where the business generates orders)
        $orders = DeliveryOrder::with('store')->get(['id', 'store_id']);

        // 2. Fetch Favors & Customer Requests (Origin: Pickup location where the user/company solicits the service)
        $favors = Favor::whereNotNull('pickup_lat')
            ->where('pickup_lat', '!=', 0)
            ->get(['id', 'pickup_lat', 'pickup_lng']);

        // 3. Registered Stores & Businesses
        $stores = \App\Models\Store::whereNotNull('latitude')
            ->where('latitude', '!=', 0)
            ->whereNotNull('longitude')
            ->where('longitude', '!=', 0)
            ->get(['id', 'name', 'latitude', 'longitude']);

        $allPoints = [];

        // Add Store Order Origins (Businesses requesting shipping)
        foreach ($orders as $order) {
            if ($order->store && $order->store->latitude && (float)$order->store->latitude != 0) {
                $allPoints[] = [
                    'lat'  => (float)$order->store->latitude,
                    'lng'  => (float)$order->store->longitude,
                    'type' => 'company_request',
                ];
            }
        }

        // Add Customer / Business Favor Request Origins (Solicitor Location Only - NO Destinations)
        foreach ($favors as $favor) {
            if ($favor->pickup_lat && $favor->pickup_lng && (float)$favor->pickup_lat != 0) {
                $allPoints[] = [
                    'lat'  => (float)$favor->pickup_lat,
                    'lng'  => (float)$favor->pickup_lng,
                    'type' => 'solicitor_origin',
                ];
            }
        }

        // Add Registered Store / Merchant Locations
        foreach ($stores as $store) {
            $allPoints[] = [
                'lat'  => (float)$store->latitude,
                'lng'  => (float)$store->longitude,
                'type' => 'company_request',
            ];
        }

        // 4. Calculate Aggregate Demand Hotspots (Only origin/solicitor locations, min 3 orders)
        $clusters = [];
        foreach ($allPoints as $pt) {
            // Group by ~250m grid cell accuracy
            $key = round($pt['lat'], 2) . '_' . round($pt['lng'], 2);
            if (!isset($clusters[$key])) {
                $clusters[$key] = ['lats' => [], 'lngs' => [], 'count' => 0];
            }
            $clusters[$key]['lats'][] = $pt['lat'];
            $clusters[$key]['lngs'][] = $pt['lng'];
            $clusters[$key]['count']++;
        }

        // Sort clusters by count descending (highest aggregated volume first)
        uasort($clusters, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $hotspots = [];
        $index = 0;
        foreach ($clusters as $key => $data) {
            // Filter: Only show zones with aggregated volume (at least 3 or more orders in the sector)
            if ($data['count'] < 3) {
                continue;
            }

            $avgLat = array_sum($data['lats']) / count($data['lats']);
            $avgLng = array_sum($data['lngs']) / count($data['lngs']);
            $count  = $data['count'];

            // Scale weight and radius based on total order density in the zone
            $weight = min(1.2 + ($count * 0.3), 4.0);
            $radius = min(280 + ($count * 60), 850);

            $hotspots[] = [
                'id'       => 'hotspot_' . ($index++),
                'lat'      => $avgLat,
                'lng'      => $avgLng,
                'count'    => $count,
                'weight'   => $weight,
                'radius'   => $radius,
            ];
        }

        return apiResponse('heatmap_data', 'success', ['Datos del mapa de calor'], [
            'hotspots' => $hotspots,
        ]);
    }

    public function currentDemand(Request $request)
    {
        $driver = $this->driver();
        $lat = (float) ($request->latitude ?? $driver->latitude ?? -12.04318);
        $lng = (float) ($request->longitude ?? $driver->longitude ?? -77.02824);
        $radius = (float) ($request->radius ?? 5.0);

        $demand = \App\Services\DemandEngine::calculateDemandTier($lat, $lng, $radius);

        return apiResponse('demand_status', 'success', ['Estado de demanda actual'], $demand);
    }

    public function farePreview(Request $request)
    {
        $distanceKm = (float) ($request->distance_km ?? 1.0);
        $durationMin = (float) ($request->duration_minutes ?? \App\Services\RouteOptimizationService::estimateTimeMinutes($distanceKm));
        $orderCount = (int) ($request->order_count ?? 1);
        $demandTier = $request->demand_tier ?? \App\Services\DemandEngine::TIER_NORMAL;
        $tip = (float) ($request->tip ?? 0.0);

        $fare = \App\Services\DriverFareEngine::calculateRouteFare($distanceKm, $durationMin, $orderCount, $demandTier, $tip);

        return apiResponse('fare_preview', 'success', ['Cálculo dinámico de ganancia'], $fare);
    }

    public function activeBatch(Request $request)
    {
        $driver = $this->driver();
        $batch = \App\Models\CourierBatch::where('driver_id', $driver->id)
            ->whereIn('status', [\App\Models\CourierBatch::STATUS_ACCEPTED, \App\Models\CourierBatch::STATUS_IN_PROGRESS])
            ->with(['batchOrders.order'])
            ->latest('id')
            ->first();

        if (!$batch) {
            return apiResponse('active_batch', 'success', ['No hay lotes activos actualmente'], [
                'batch' => null,
            ]);
        }

        return apiResponse('active_batch', 'success', ['Lote activo encontrado'], [
            'batch' => $this->formatBatch($batch),
        ]);
    }

    public function batchDetail(Request $request, $id)
    {
        $driver = $this->driver();
        $batch = \App\Models\CourierBatch::where(function ($q) use ($driver) {
                $q->where('driver_id', $driver->id)
                  ->orWhere('status', \App\Models\CourierBatch::STATUS_OFFERED)
                  ->orWhere('status', \App\Models\CourierBatch::STATUS_PENDING);
            })
            ->with(['batchOrders.order'])
            ->findOrFail($id);

        return apiResponse('batch_detail', 'success', ['Detalle del lote'], [
            'batch' => $this->formatBatch($batch),
        ]);
    }

    public function acceptBatch(Request $request, $id)
    {
        $driver = $this->driver();

        // 1. Economic eligibility check
        $economicCheck = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
        if (!$economicCheck['allowed']) {
            return apiResponse('insufficient_balance', 'error', [
                $economicCheck['reason'] ?? 'Recarga tu wallet para aceptar este lote.',
            ], [
                'economic_state' => $economicCheck['economic_state'],
                'min_recharge'   => $economicCheck['min_recharge'],
                'balance'        => $economicCheck['balance'],
            ]);
        }

        $batch = \App\Models\CourierBatch::whereIn('status', [
            \App\Models\CourierBatch::STATUS_PENDING,
            \App\Models\CourierBatch::STATUS_OFFERED,
        ])->findOrFail($id);

        DB::transaction(function () use ($batch, $driver) {
            $batch->update([
                'driver_id'   => $driver->id,
                'status'      => \App\Models\CourierBatch::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            // Assign driver to all underlying orders
            foreach ($batch->batchOrders as $bo) {
                $orderModel = $bo->order;
                if ($orderModel instanceof DeliveryOrder) {
                    $orderModel->update([
                        'driver_id'          => $driver->id,
                        'status'             => 'on_way',
                        'driver_assigned_at' => now(),
                    ]);
                } elseif ($orderModel instanceof Favor) {
                    $orderModel->update([
                        'courier_id'          => $driver->id,
                        'status'              => 'accepted',
                        'courier_assigned_at' => now(),
                    ]);
                }
            }
        });

        return apiResponse('batch_accepted', 'success', ['Lote aceptado exitosamente'], [
            'batch' => $this->formatBatch($batch->fresh(['batchOrders.order'])),
        ]);
    }

    public function completeBatchStop(Request $request, $id, $stopNumber)
    {
        $driver = $this->driver();
        $batch = \App\Models\CourierBatch::where('driver_id', $driver->id)
            ->whereIn('status', [\App\Models\CourierBatch::STATUS_ACCEPTED, \App\Models\CourierBatch::STATUS_IN_PROGRESS])
            ->with('batchOrders.order')
            ->findOrFail($id);

        $stops = $batch->optimized_stops ?? [];
        $targetStop = null;
        foreach ($stops as $s) {
            if ($s['stop_number'] == $stopNumber) {
                $targetStop = $s;
                break;
            }
        }

        if (!$targetStop) {
            return apiResponse('stop_not_found', 'error', ['Parada no encontrada en el itinerario.']);
        }

        $batchOrder = $batch->batchOrders()
            ->where('order_id', $targetStop['order_id'])
            ->first();

        if (!$batchOrder) {
            return apiResponse('order_not_found', 'error', ['Orden del lote no encontrada.']);
        }

        DB::transaction(function () use ($batch, $batchOrder, $targetStop, $driver) {
            if ($targetStop['type'] === 'pickup') {
                $batchOrder->update([
                    'status'       => \App\Models\CourierBatchOrder::STATUS_PICKED_UP,
                    'picked_up_at' => now(),
                ]);
                $batch->update(['status' => \App\Models\CourierBatch::STATUS_IN_PROGRESS]);
            } else {
                // Dropoff completed
                \App\Services\BatchingEngine::completeOrderInBatch($batchOrder);

                $order = $batchOrder->order;
                if ($order instanceof DeliveryOrder) {
                    $order->update(['status' => 'delivered', 'delivered_at' => now()]);
                } elseif ($order instanceof Favor) {
                    $order->update(['status' => 'delivered', 'delivered_at' => now()]);
                }

                // Register driver financial earning
                $earningAmount = (float) $batchOrder->individual_earning;
                $courierEarning = \App\Models\CourierEarning::firstOrCreate([
                    'courier_id' => $driver->id,
                    'job_type'   => $batchOrder->order_type,
                    'job_id'     => $batchOrder->order_id,
                ], [
                    'amount'      => $earningAmount,
                    'commission'  => 0,
                    'description' => "Entrega de lote {$batch->batch_type} #{$batch->batch_no} (Parada {$targetStop['stop_number']})",
                ]);

                if ($courierEarning->wasRecentlyCreated) {
                    \App\Services\DeliveryFinancialLedger::recordDriverEarning($driver, $earningAmount, $courierEarning);
                }

                // Economic Policy: If in promotional balance, 100% retained.
                // If on standard recharge balance, consume single fee (never multiplied per batch!)
                $economicCheck = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
                if ($economicCheck['balance_type'] !== 'promotional') {
                    \App\Services\DriverEconomicPolicyService::consumeBalance(
                        $driver,
                        $earningAmount * 0.10, // standard nominal deduction or base
                        "Servicio Lote {$batch->batch_no} (Parada {$targetStop['stop_number']})",
                        $order
                    );
                }
            }
        });

        return apiResponse('stop_completed', 'success', ['Parada completada con éxito'], [
            'batch' => $this->formatBatch($batch->fresh(['batchOrders.order'])),
        ]);
    }

    private function formatBatch(\App\Models\CourierBatch $batch): array
    {
        return [
            'id'                     => $batch->id,
            'batch_no'               => $batch->batch_no,
            'batch_type'             => $batch->batch_type,
            'status'                 => $batch->status,
            'total_orders'           => (int) $batch->total_orders,
            'total_distance_km'      => (float) $batch->total_distance_km,
            'total_duration_minutes' => (float) $batch->total_duration_minutes,
            'base_earning'           => (float) $batch->base_earning,
            'distance_earning'       => (float) $batch->distance_earning,
            'time_earning'           => (float) $batch->time_earning,
            'batch_bonus'            => (float) $batch->batch_bonus,
            'demand_incentive'       => (float) $batch->demand_incentive,
            'driver_earning'         => (float) $batch->driver_earning,
            'total_tips'             => (float) $batch->total_tips,
            'total_payout'           => (float) $batch->total_payout,
            'total_points'           => (int) $batch->total_points,
            'demand_tier'            => $batch->demand_tier,
            'demand_multiplier'      => (float) $batch->demand_multiplier,
            'optimized_stops'        => $batch->optimized_stops ?? [],
            'fare_breakdown'         => $batch->fare_breakdown ?? [],
            'orders'                 => $batch->batchOrders->map(function ($bo) {
                return [
                    'id'                 => $bo->id,
                    'order_id'           => $bo->order_id,
                    'order_type'         => $bo->order_type,
                    'sequence_order'     => $bo->sequence_order,
                    'pickup_stop_no'     => $bo->pickup_stop_no,
                    'dropoff_stop_no'    => $bo->dropoff_stop_no,
                    'status'             => $bo->status,
                    'individual_earning' => (float) $bo->individual_earning,
                    'tip'                => (float) $bo->tip,
                    'points'             => (int) $bo->points,
                    'order_detail'       => $bo->order ? (
                        $bo->order instanceof DeliveryOrder
                            ? $this->formatJob($bo->order, 'delivery')
                            : $this->formatFavorJob($bo->order)
                    ) : null,
                ];
            })->values(),
            'created_at'             => optional($batch->created_at)->toIso8601String(),
        ];
    }

    public function getAutoAcceptSettings()
    {
        $driver = $this->driver();

        return apiResponse('auto_accept_settings', 'success', ['Configuración de autoaceptación'], [
            'auto_accept_enabled'      => (bool) $driver->auto_accept_enabled,
            'auto_accept_min_earning'  => (float) ($driver->auto_accept_min_earning ?? 0.0),
            'auto_accept_max_distance' => (float) ($driver->auto_accept_max_distance ?? 10.0),
        ]);
    }

    public function updateAutoAcceptSettings(Request $request)
    {
        $driver = $this->driver();

        $validator = Validator::make($request->all(), [
            'auto_accept_enabled'      => 'required|boolean',
            'auto_accept_min_earning'  => 'nullable|numeric|min:0',
            'auto_accept_max_distance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $driver->update([
            'auto_accept_enabled'      => (bool) $request->auto_accept_enabled,
            'auto_accept_min_earning'  => (float) ($request->auto_accept_min_earning ?? 0.0),
            'auto_accept_max_distance' => (float) ($request->auto_accept_max_distance ?? 10.0),
        ]);

        return apiResponse('auto_accept_settings_updated', 'success', ['Preferencias de autoaceptación actualizadas correctamente'], [
            'auto_accept_enabled'      => (bool) $driver->auto_accept_enabled,
            'auto_accept_min_earning'  => (float) $driver->auto_accept_min_earning,
            'auto_accept_max_distance' => (float) $driver->auto_accept_max_distance,
        ]);
    }

    public function pendingOffers(Request $request)
    {
        $driver = $this->driver();

        // Expire any past-due offers first
        \App\Services\OfferDispatchService::processExpiredOffers();

        $offers = \App\Models\CourierJobOffer::where('driver_id', $driver->id)
            ->where('status', 'offered')
            ->where('expires_at', '>', now())
            ->with(['job', 'batch.batchOrders.order'])
            ->latest('id')
            ->get()
            ->map(function ($offer) {
                $rem = max(0, $offer->expires_at ? now()->diffInSeconds($offer->expires_at, false) : 0);
                $job = $offer->job;

                $jobData = null;
                if ($job instanceof DeliveryOrder) {
                    $jobData = $this->formatJob($job, 'delivery');
                } elseif ($job instanceof Favor) {
                    $jobData = $this->formatFavorJob($job);
                } elseif ($job instanceof \App\Models\CourierBatch) {
                    $jobData = $this->formatBatch($job);
                }

                return [
                    'id'                => $offer->id,
                    'driver_id'         => $offer->driver_id,
                    'job_type'          => $offer->job_type,
                    'job_id'            => $offer->job_id,
                    'batch_id'          => $offer->batch_id,
                    'source'            => $offer->source,
                    'status'            => $offer->status,
                    'offered_at'        => optional($offer->offered_at)->toIso8601String(),
                    'expires_at'        => optional($offer->expires_at)->toIso8601String(),
                    'remaining_seconds' => (int) $rem,
                    'job_detail'        => $jobData,
                ];
            });

        return apiResponse('pending_offers', 'success', ['Ofertas activas'], [
            'offers' => $offers,
        ]);
    }

    public function acceptOffer(Request $request, $id)
    {
        $driver = $this->driver();
        $result = \App\Services\OfferDispatchService::respondToOffer($driver, (int) $id, 'accept');

        if (!$result['success']) {
            return apiResponse('offer_accept_failed', 'error', [$result['message']], $result['economic_status'] ?? []);
        }

        return apiResponse('offer_accepted', 'success', [$result['message']], [
            'job' => $result['job'],
        ]);
    }

    public function rejectOffer(Request $request, $id)
    {
        $driver = $this->driver();
        $result = \App\Services\OfferDispatchService::respondToOffer($driver, (int) $id, 'reject');

        return apiResponse('offer_rejected', 'success', [$result['message']]);
    }
}
