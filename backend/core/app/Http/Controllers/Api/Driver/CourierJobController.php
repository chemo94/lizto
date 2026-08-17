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

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = (float) ($request->radius ?? gs('delivery_coverage_radius') ?? 8);

        $deliveryQuery = DeliveryOrder::whereNull('driver_id')
            ->where('status', 'ready')
            ->with('store', 'user');

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
            $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

            $deliveryQuery->whereHas('store', function ($q) use ($haversine, $radius) {
                $q->whereRaw("$haversine <= $radius");
            });

            $favorQuery->whereRaw("$haversine <= $radius");
        }

        $deliveryJobs = $deliveryQuery->get()->map(fn($o) => $this->formatJob($o, 'delivery'));
        $favorJobs = $favorQuery->get()->map(fn($o) => $this->formatFavorJob($o));

        $jobs = $deliveryJobs->concat($favorJobs)->sortByDesc('created_at')->values();

        return apiResponse('pending_jobs', 'success', ['Pedidos disponibles'], [
            'jobs' => $jobs,
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

        $jobs = $deliveryJobs->concat($favorJobs)->sortByDesc('created_at')->values();

        return apiResponse('job_history', 'success', ['Historial de pedidos'], [
            'jobs' => $jobs,
        ]);
    }

    public function jobDetail(Request $request, $id)
    {
        $driver = $this->driver();
        $type = $request->type;

        if ($type === 'favor') {
            $job = Favor::where('courier_id', $driver->id)->with('user', 'messages')->findOrFail($id);
            return apiResponse('job_detail', 'success', ['Detalle del pedido'], [
                'job' => $this->formatFavorJob($job),
            ]);
        }

        $job = DeliveryOrder::where('driver_id', $driver->id)->with('store', 'user', 'items')->findOrFail($id);
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

        // Verificar wallet balance
        $wallet = $driver->wallet;
        $balance = $wallet ? $wallet->balance : 0;
        if ($balance <= 0) {
            return apiResponse('insufficient_balance', 'error', ['Recarga tu wallet para aceptar pedidos. Saldo actual: S/ 0.00']);
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
                FcmService::sendToAllCouriers(
                    'Envío tomado',
                    'Un repartidor aceptó el envío #' . $job->order_no,
                    ['type' => 'favor_taken', 'favor_id' => (string) $job->id]
                );
                return apiResponse('job_accepted', 'success', ['Envío aceptado directamente'], [
                    'job' => $this->formatFavorJob($job->fresh()),
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

        // Only accept if status is 'ready'
        $job = DeliveryOrder::where('status', 'ready')->whereNull('driver_id')->findOrFail($id);
        $job->update([
            'driver_id'          => $driver->id,
            'status'             => 'on_way',
            'driver_assigned_at' => now(),
        ]);

        event(new DeliveryOrderStatusUpdated($job->fresh('store', 'user')));
        FcmService::sendToUser($job->user, 'Repartidor asignado', 'Tu pedido #' . $job->order_no . ' será entregado pronto', ['order_id' => (string) $job->id, 'order_no' => $job->order_no, 'type' => 'driver_assigned']);
        if ($job->store && $job->store->seller) {
            FcmService::sendToSeller($job->store->seller, 'Repartidor asignado', 'El repartidor ' . $driver->name . ' ha aceptado el pedido #' . $job->order_no, ['order_id' => (string) $job->id, 'order_no' => $job->order_no, 'type' => 'driver_assigned']);
        }

        return apiResponse('job_accepted', 'success', ['Pedido aceptado'], [
            'job' => $this->formatJob($job->fresh('store', 'user'), 'delivery'),
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
            $baseDeliveryPercent = $commission?->delivery_percent ?? 20;
            $baseFavorPercent = $commission?->favor_percent ?? 20;
            $minCommission = $commission?->min_commission ?? 1;

            $basePercent = $type === 'favor' ? $baseFavorPercent : $baseDeliveryPercent;
            $dynamicTier = DeliveryFinancialLedger::getDriverDynamicCommissionPercent($driver->id, (float) $basePercent);
            $commissionPercent = $dynamicTier['effective_percent'];

            if ($type === 'favor') {
                $deliveryFee = $job->total ?? 0;
                $commissionAmount = max($deliveryFee * $commissionPercent / 100, $minCommission);
            } else {
                $deliveryFee = $job->delivery_fee ?? 0;
                $commissionAmount = max($deliveryFee * $commissionPercent / 100, $minCommission);
            }

            // Update earning record
            $courierEarning = CourierEarning::firstOrCreate([
                'courier_id'  => $driver->id,
                'job_type'    => $type === 'favor' ? Favor::class : DeliveryOrder::class,
                'job_id'      => $job->id,
            ], [
                // La comisión se descuenta de la recarga/wallet, no de la ganancia generada.
                'amount'      => $deliveryFee,
                'commission'  => $commissionAmount,
                'description' => $type === 'favor' ? 'Entrega de favor' : 'Entrega de pedido #' . $job->order_no,
            ]);

            if ($courierEarning->wasRecentlyCreated) {
                DeliveryFinancialLedger::recordDriverEarning($driver, (float) $deliveryFee, $courierEarning);
            }

            if ($paymentChannel === 'cash') {
                DeliveryFinancialLedger::recordCashCollection($driver, (float) ($job->total ?? 0), $job);
            }

            $job->update(['commission_amount' => $commissionAmount]);

            // Debit commission from driver wallet
            if ($courierEarning->wasRecentlyCreated) {
            $this->ensureWallet($driver);
            $driver->wallet->debit($commissionAmount, 'commission', 'Comisión por pedido #' . ($type === 'favor' ? 'Favor-' . $job->id : $job->order_no), $job);

            // Sync legacy drivers.balance + transactions table (lo que lee la app)
            $driver->balance = $driver->wallet->balance;
            $driver->save();

            $trx = new Transaction();
            $trx->driver_id    = $driver->id;
            $trx->amount       = $commissionAmount;
            $trx->post_balance = $driver->balance;
            $trx->charge       = 0;
            $trx->trx          = getTrx();
            $trx->trx_type     = '-';
            $trx->remark       = 'commission';
            $trx->details      = 'Comisión por pedido #' . ($type === 'favor' ? 'Favor-' . $job->id : $job->order_no);
            $trx->save();
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
        $baseCommissionPercent = (float) ($commission?->delivery_percent ?? 20);
        $tierInfo = DeliveryFinancialLedger::getDriverDynamicCommissionPercent($driver->id, $baseCommissionPercent);

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
            'total_weekly_jobs'    => $tierInfo['total_weekly_jobs'],
            'next_tier_needed'     => $tierInfo['next_tier_needed'],
            'next_tier_name'       => $tierInfo['next_tier_name'],
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

        return [
            'id'              => $order->id,
            'type'            => $type,
            'order_no'        => $order->order_no,
            'customer_name'   => $order->user?->fullname,
            'customer_phone'  => $order->user?->mobile,
            'pickup_address'  => $order->store?->address,
            'pickup_lat'      => $order->store?->latitude,
            'pickup_lng'      => $order->store?->longitude,
            'delivery_address'=> $order->delivery_address,
            'delivery_lat'    => $order->delivery_lat,
            'delivery_lng'    => $order->delivery_lng,
            'amount'          => $order->total,
            'delivery_fee'    => $order->delivery_fee,
            'total_earning'   => $order->delivery_fee ?? 0,
            'status'          => $order->status,
            'store_name'      => $order->store?->name,
            'description'     => $order->notes,
            'payment_method_code' => (string) ($order->payment_method_code ?? '0'),
            'payment_method_name' => $paymentName,
            'payment_status'  => (int) $order->payment_status,
            'requires_payment_collection' => !(bool) $order->payment_status,
            'payment_wallet'  => $wallet,
            'payment_qr_string' => $qrString,
            'created_at'      => $order->created_at,
            'updated_at'      => $order->updated_at,
        ];
    }

    private function formatFavorJob($favor)
    {
        $fee = $favor->delivery_fee ?? $favor->total ?? 0;

        $distanceKm = null;
        if ($favor->pickup_lat && $favor->pickup_lng && $favor->delivery_lat && $favor->delivery_lng) {
            $distanceKm = round(\App\Support\DeliveryPricing::distanceKm(
                (float) $favor->pickup_lat, (float) $favor->pickup_lng,
                (float) $favor->delivery_lat, (float) $favor->delivery_lng
            ), 2);
        }

        $isShortDistance = ($distanceKm !== null && $distanceKm < 1.0) || ($favor->delivery_fee == 4.0 && $distanceKm && $distanceKm < 1.5);

        return [
            'id'              => $favor->id,
            'type'            => 'favor',
            'order_no'        => $favor->order_no,
            'customer_name'   => $favor->user?->fullname,
            'customer_phone'  => $favor->user?->mobile,
            'pickup_address'  => $favor->pickup_address,
            'pickup_lat'      => $favor->pickup_lat,
            'pickup_lng'      => $favor->pickup_lng,
            'delivery_address'=> $favor->delivery_address,
            'delivery_lat'    => $favor->delivery_lat,
            'delivery_lng'    => $favor->delivery_lng,
            'stops'           => $favor->stops,
            'distance_km'     => $distanceKm,
            'amount'          => $favor->estimated_amount,
            'delivery_fee'    => $favor->delivery_fee,
            'total_earning'   => $fee,
            'status'          => $favor->status,
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
        if (!$job->user) return;
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
            FcmService::sendToUser($job->user, $messages[0], $messages[1], $data);
        } else {
            FcmService::sendToUser($job->user, $messages[0], $messages[1], $data);
            if ($job->store && $job->store->seller) {
                $sellerData = array_merge($data, ['order_id' => (string) $job->id]);
                FcmService::sendToSeller($job->store->seller, $messages[0], 'Pedido #' . $job->order_no . ': ' . $messages[1], $sellerData);
            }
        }
    }

    public function walletTransactions()
    {
        $driver = $this->driver();
        $this->ensureWallet($driver);

        $transactions = $driver->wallet->transactions()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($tx) => [
                'id'           => $tx->id,
                'trx'          => $tx->trx,
                'amount'       => $tx->amount,
                'post_balance' => $tx->post_balance,
                'charge'       => $tx->charge,
                'trx_type'     => $tx->trx_type,
                'remark'       => $tx->remark,
                'details'      => $tx->details,
                'created_at'   => $tx->created_at,
            ]);

        return apiResponse('wallet_transactions', 'success', ['Historial de transacciones'], [
            'wallet_balance' => $driver->wallet->balance,
            'transactions'   => $transactions,
        ]);
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
}
