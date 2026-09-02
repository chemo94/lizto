<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewJobAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $job;
    public $message;

    public function __construct($job, $message = null)
    {
        $this->job     = $job;
        $this->message = $message ?? 'Hay un nuevo pedido cerca de tu ubicación';
    }

    public function broadcastOn()
    {
        $courierId = $this->job->driver_id ?? $this->job->courier_id ?? null;
        $channels = [new Channel('private-nearby-couriers')];
        if ($courierId) {
            $channels[] = new Channel('private-courier.' . $courierId);
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'new_job_available';
    }

    public function broadcastWith()
    {
        $isDelivery = $this->job instanceof \App\Models\DeliveryOrder;
        return array_merge(\App\Services\FcmService::courierJobPayload($this->job), [
            'event_id'         => 'job-available-' . $this->job->id . '-' . ($this->job->updated_at?->format('Uu') ?? now()->format('Uu')),
            'job_id'           => (string) $this->job->id,
            'id'               => (int) $this->job->id,
            'type'             => $isDelivery ? 'delivery' : 'favor',
            'message'          => $this->message,
        ]);
    }
}
