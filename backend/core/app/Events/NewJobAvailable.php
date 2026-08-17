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
        if ($courierId) {
            return [new Channel('private-courier.' . $courierId)];
        }
        // The courier apps subscribe to this authorized channel while they
        // are online. Keep the event channel aligned with that subscription.
        return [new Channel('private-nearby-couriers')];
    }

    public function broadcastAs()
    {
        return 'new_job_available';
    }

    public function broadcastWith()
    {
        return [
            'event_id' => 'job-available-' . $this->job->id . '-' . ($this->job->updated_at?->format('Uu') ?? now()->format('Uu')),
            'job_id'   => $this->job->id,
            'job_type' => $this->job instanceof \App\Models\DeliveryOrder ? 'delivery' : 'favor',
            'message'  => $this->message,
        ];
    }
}
