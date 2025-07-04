<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CallAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $caller;
    public $receiver;

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param string $callId L'ID unique de l'appel.
     * @param User $caller L'utilisateur qui a initié l'appel.
     * @param User $receiver L'utilisateur qui a accepté l'appel.
     * @return void
     */
    public function __construct(string $callId, User $caller, User $receiver)
    {
        $this->callId = $callId;
        $this->caller = $caller->withoutRelations();
        $this->receiver = $receiver->withoutRelations();
    }

    /**
     * Obtenez les canaux sur lesquels l'événement doit être diffusé.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Diffuser sur les canaux privés de l'appelant et du destinataire
        return [
            new PrivateChannel('users.' . $this->caller->id), // L'appelant est notifié de l'acceptation
            new PrivateChannel('users.' . $this->receiver->id), // Le destinataire (qui a accepté) reçoit aussi la confirmation
        ];
    }

    /**
     * Le nom de l'événement de diffusion.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.accepted';
    }

    /**
     * Les données à diffuser avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'profile_picture' => $this->receiver->profile_picture,
            ],
        ];
    }
}
