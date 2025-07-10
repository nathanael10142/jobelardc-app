<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $conversationId;
    public $readerId;

    /**
     * Create a new event instance.
     *
     * @param int $messageId The ID of the message that was read.
     * @param int $conversationId The ID of the conversation the message belongs to.
     * @param int $readerId The ID of the user who read the message.
     * @return void
     */
    public function __construct($messageId, $conversationId, $readerId)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->readerId = $readerId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('conversations.' . $this->conversationId);
    }
}