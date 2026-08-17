<?php

namespace App\Services;

use App\Constants\Status;
use App\Events\FavorStatusUpdated;
use App\Models\Driver;
use App\Models\Favor;
use Illuminate\Support\Facades\DB;

class AdminDeliveryRequestDispatchService
{
    public const INITIAL_BROADCAST_WAIT_SECONDS = 60;
    public const TARGETED_WAIT_SECONDS = 30;

    /**
     * Rotate requests whose current response window has elapsed.
     */
    public static function processDueRequests(): int
    {
        return DB::transaction(function () {
            $favors = Favor::query()
                ->where('status', 'searching_courier')
                ->whereIn('dispatch_mode', ['admin_broadcast', 'admin_targeted'])
                ->whereNotNull('dispatch_timeout_at')
                ->where('dispatch_timeout_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($favors as $favor) {
                self::targetNextCourier($favor);
            }

            return $favors->count();
        });
    }

    /**
     * Sends the request to one new available courier for a 30-second window.
     */
    public static function targetNextCourier(Favor $favor): ?Driver
    {
        if ($favor->status !== 'searching_courier') {
            return null;
        }

        $attemptedIds = collect($favor->dispatch_attempted_driver_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $courier = Driver::query()
            ->where('status', Status::ENABLE)
            ->where('online_status', 1)
            ->whereIn('service_type', ['delivery', 'both'])
            ->whereHas('wallet', fn ($query) => $query->where('balance', '>', 0))
            ->when($attemptedIds, fn ($query) => $query->whereNotIn('id', $attemptedIds))
            ->orderBy('id')
            ->first();

        if (!$courier) {
            // Start a new round if every currently available courier was consulted.
            $attemptedIds = [];
            $courier = Driver::query()
                ->where('status', Status::ENABLE)
                ->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])
                ->whereHas('wallet', fn ($query) => $query->where('balance', '>', 0))
                ->orderBy('id')
                ->first();
        }

        if (!$courier) {
            // Keep the request eligible for a future courier coming online.
            $favor->update(['dispatch_timeout_at' => now()->addSeconds(self::TARGETED_WAIT_SECONDS)]);
            return null;
        }

        $attemptedIds[] = $courier->id;
        $favor->update([
            'courier_id'                   => $courier->id,
            'dispatch_mode'                => 'admin_targeted',
            'dispatch_attempted_driver_ids'=> array_values(array_unique($attemptedIds)),
            'dispatch_timeout_at'          => now()->addSeconds(self::TARGETED_WAIT_SECONDS),
        ]);

        FcmService::sendToDriver(
            $courier,
            '¿Puedes realizar este envío?',
            'Responde al envío #' . $favor->order_no . ' en los próximos 30 segundos.',
            [
                'type'         => 'targeted_delivery_request',
                'favor_id'     => (string) $favor->id,
                'order_no'     => $favor->order_no,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        );

        event(new FavorStatusUpdated($favor->fresh(), 'waiting_courier_response'));

        return $courier;
    }
}
