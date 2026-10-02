<?php

namespace App\Services;

use App\Models\CourierJobOffer;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Model;

class CourierOfferTracker
{
    public static function offered(Driver $driver, Model $job, string $source, bool $notificationDelivered, $expiresAt = null): CourierJobOffer
    {
        $batchId = $job instanceof \App\Models\CourierBatch ? $job->id : ($job->courier_batch_id ?? null);
        return CourierJobOffer::create([
            'driver_id' => $driver->id,
            'batch_id'  => $batchId,
            'job_type'  => $job::class,
            'job_id'    => $job->id,
            'source'    => $source,
            'status'    => $notificationDelivered ? 'offered' : 'failed',
            'notification_delivered' => $notificationDelivered,
            'offered_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    public static function respond(Driver $driver, Model $job, string $status): void
    {
        CourierJobOffer::where('driver_id', $driver->id)
            ->where('job_type', $job::class)
            ->where('job_id', $job->id)
            ->where('status', 'offered')
            ->latest('id')
            ->limit(1)
            ->update(['status' => $status, 'responded_at' => now()]);
    }

    public static function expireOpen(Model $job, ?int $driverId = null): void
    {
        CourierJobOffer::where('job_type', $job::class)
            ->where('job_id', $job->id)
            ->where('status', 'offered')
            ->when($driverId, fn ($query) => $query->where('driver_id', $driverId))
            ->update(['status' => 'expired']);
    }
}
