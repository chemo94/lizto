<?php

namespace App\Lib;

use App\Constants\Status;
use App\Events\Ride as EventsRide;
use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Support\Facades\Cache;

class ManageRideQueue
{
    public function initQueue($queue)
    {
        $this->queueAction($queue);
    }

    private function queueAction($queue)
    {
        return match ($queue->action_type) {
            'new_driver_notification' => $this->sendNewRideNotificationToDriver($queue),
            default => throw new \Exception("Unknown queue action: {$queue->action_type}"),
        };
    }

    public function sendNewRideNotificationToDriver($queue)
    {
        $ride = Ride::pending()->find($queue->ride_id);
        if (!$ride) {
            return;
        }

        $ride->load('user', 'service', 'driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year');
        $requiredCapacity = max((int) $ride->number_of_passenger, (int) ($ride->service?->min_vehicle_capacity ?? 1));
        $shortCode = [
            'type'                  => 'new_ride',
            'ride_id'               => (string) $ride->id,
            'id'                    => (string) $ride->id,
            'uid'                   => (string) $ride->uid,
            'service'               => $ride->service?->name ?? 'Taxi',
            'pickup_location'       => (string) $ride->pickup_location,
            'pickup_latitude'       => (string) ($ride->pickup_latitude ?? ''),
            'pickup_longitude'      => (string) ($ride->pickup_longitude ?? ''),
            'destination'           => (string) $ride->destination,
            'destination_latitude'  => (string) ($ride->destination_latitude ?? ''),
            'destination_longitude' => (string) ($ride->destination_longitude ?? ''),
            'duration'              => (string) $ride->duration,
            'distance'              => (string) $ride->distance,
            'amount'                => (string) ($ride->amount ?? $ride->recommend_amount ?? '0.00'),
            'min_amount'            => (string) ($ride->min_amount ?? '0.00'),
            'max_amount'            => (string) ($ride->max_amount ?? '0.00'),
            'pickup_time'           => showDateTime(now()),
            'rider_name'            => $ride->user?->fullname ?? 'Usuario',
            'rider_avatar'          => (string) ($ride->user?->image ?? ''),
            'expires_at'            => now()->addSeconds(30)->toISOString(),
            'seconds_remaining'     => '30',
            'for_app'               => 'RIDE-' . $ride->id,
            'template_name'         => 'NEW_RIDE',
        ];
        $driverImagePath = getFilePath('driver');
        $userImagePath = getFilePath('user');

        $driverQuery = Driver::active()
            ->where('online_status', Status::YES)
            ->whereIn('service_type', ['ride', 'both'])
            ->where('service_id', $ride->service_id)
            ->whereHas('vehicle', fn ($query) => $query->where('passenger_capacity', '>=', $requiredCapacity))
            ->where('dv', Status::VERIFIED)
            ->where('vv', Status::VERIFIED)
            ->notRunning();
        if ($ride->pickup_zone_id) {
            $driverQuery->where('zone_id', $ride->pickup_zone_id);
        }

        $driverQuery->chunk(20, function ($drivers) use ($ride, $shortCode, $driverImagePath, $userImagePath) {
            foreach ($drivers as $driver) {
                if (Cache::has("driver_rejected_{$driver->id}_{$ride->id}")) {
                    continue;
                }
                if (class_exists(\App\Services\DriverEconomicPolicyService::class)) {
                    $check = \App\Services\DriverEconomicPolicyService::canDriverReceiveOrders($driver);
                    if (!$check['allowed']) {
                        continue;
                    }
                }
                $payload = [
                    'ride' => $ride,
                    'short_code' => $shortCode,
                    'driver_image_path' => $driverImagePath,
                    'user_image_path' => $userImagePath,
                ];
                try {
                    event(new EventsRide("rider-driver-$driver->id", 'NEW_RIDE', $payload));
                } catch (\Exception $e) {
                    \Log::error('NEW_RIDE broadcast failed: ' . $e->getMessage());
                }
                try {
                    \App\Services\RealtimePublisher::rideEvent((int) $ride->id, 'NEW_RIDE', $payload, (int) $driver->id, (int) ($ride->user_id ?? 0));
                } catch (\Exception $e) {
                    \Log::error('NEW_RIDE realtime publish failed: ' . $e->getMessage());
                }
                notify($driver, 'NEW_RIDE', $shortCode, ['push']);
            }
        });
        $queue->delete();
    }
}
