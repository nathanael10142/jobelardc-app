<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $ender; // L'utilisateur qui a mis fin à l'appel
    public $otherParticipant; // L'autre utilisateur impliqué dans l'appel

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param string $callId L'ID unique de l'appel.
     * @param User $ender L'utilisateur qui a mis fin à l'appel.
     * @param User $otherParticipant L'autre participant à l'appel.
     * @return void
     */
    public function __construct(string $callId, User $ender, User $otherParticipant)
    {
        $this->callId = $callId;
        $this->ender = $ender->withoutRelations();
        $this->otherParticipant = $otherParticipant->withoutRelations();
    }

    /**
     * Obtenez les canaux sur lesquels l'événement doit être diffusé.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Diffuser sur le canal privé de l'autre participant
        return [
            new PrivateChannel('users.' . $this->otherParticipant->id), // L'autre participant est notifié de la fin de l'appel
        ];
    }

    /**
     * Le nom de l'événement de diffusion.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.ended';
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
            'ender' => [
                'id' => $this->ender->id,
                'name' => $this->ender->name,
                'profile_picture' => $this->ender->profile_picture,
            ],
        ];
    }
}
