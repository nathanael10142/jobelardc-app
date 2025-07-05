    <?php

    namespace App\Events;

    use Illuminate\Broadcasting\Channel;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Broadcasting\PresenceChannel;
    use Illuminate\Broadcasting\PrivateChannel;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Queue\SerializesModels;
    use App\Models\User; // Assurez-vous d'importer le modèle User

    class CallSignal implements ShouldBroadcast
    {
        use Dispatchable, InteractsWithSockets, SerializesModels;

        public $callId;
        public $sender;
        public $receiver;
        public $type; // 'offer', 'answer', 'ice-candidate'
        public $payload; // Les données SDP ou ICE Candidate

        /**
         * Create a new event instance.
         *
         * @return void
         */
        public function __construct(string $callId, User $sender, User $receiver, string $type, array $payload)
        {
            $this->callId = $callId;
            $this->sender = $sender;
            $this->receiver = $receiver;
            $this->type = $type;
            $this->payload = $payload;
        }

        /**
         * Get the channels the event should broadcast on.
         * Le signal doit être envoyé au canal privé du RECEVEUR.
         *
         * @return array<int, \Illuminate\Broadcasting\Channel>
         */
        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('calls.' . $this->receiver->id),
            ];
        }

        /**
         * The event's broadcast name.
         *
         * @return string
         */
        public function broadcastAs(): string
        {
            return 'signal'; // Le nom de l'événement sera '.signal' côté frontend
        }

        /**
         * Get the data to broadcast.
         *
         * @return array
         */
        public function broadcastWith(): array
        {
            return [
                'call_id' => $this->callId,
                'sender_id' => $this->sender->id,
                'receiver_id' => $this->receiver->id,
                'type' => $this->type,
                'payload' => $this->payload,
            ];
        }
    }
    