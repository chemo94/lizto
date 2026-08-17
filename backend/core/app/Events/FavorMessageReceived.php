<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FavorMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return [
            new Channel('private-favor.' . $this->message->favor_id),
            new Channel('private-job.' . $this->message->favor_id),
        ];
    }

    public function broadcastAs()
    {
        return 'job_message_received';
    }

    public function broadcastWith()
    {
        return [
            'id'          => $this->message->id,
            'job_id'      => $this->message->favor_id,
            'favor_id'    => $this->message->favor_id,
            'sender_id'   => $this->message->sender_id,
            'sender_name' => $this->message->sender_name,
            'sender_role' => $this->message->sender_role,
            'message'     => $this->message->message,
            'image'       => $this->message->image ? getFilePath('chat') . '/' . $this->message->image : null,
            'created_at'  => $this->message->created_at instanceof \DateTime
                ? $this->message->created_at->toIso8601String()
                : $this->message->created_at,
        ];
    }
}
