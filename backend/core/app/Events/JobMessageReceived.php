<?php

namespace App\Events;

use App\Models\JobMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(JobMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [new Channel('private-job.' . $this->message->job_id)];
    }

    public function broadcastAs(): string
    {
        return 'job_message_received';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'job_id'      => $this->message->job_id,
            'sender_id'   => $this->message->sender_id,
            'sender_name' => $this->message->sender_name,
            'sender_role' => $this->message->sender_role,
            'message'     => $this->message->message,
            'image'       => $this->message->image ? getFilePath('chat') . '/' . $this->message->image : null,
            'created_at'  => $this->message->created_at->toIso8601String(),
        ];
    }
}
