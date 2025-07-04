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

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callId;
    public $caller;
    public $receiver;
    public $callType;

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param string $callId L'ID unique de l'appel.
     * @param User $caller L'utilisateur qui initie l'appel.
     * @param User $receiver L'utilisateur qui reçoit l'appel.
     * @param string $callType Le type d'appel ('audio' ou 'video').
     * @return void
     */
    public function __construct(string $callId, User $caller, User $receiver, string $callType)
    {
        $this->callId = $callId;
        $this->caller = $caller->withoutRelations(); // Évite les boucles infinies de sérialisation
        $this->receiver = $receiver->withoutRelations();
        $this->callType = $callType;
    }

    /**
     * Obtenez les canaux sur lesquels l'événement doit être diffusé.
     * L'appelant et le destinataire doivent tous deux écouter cet événement.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Un canal privé pour chaque utilisateur impliqué dans l'appel
        return [
            new PrivateChannel('users.' . $this->receiver->id), // Le destinataire reçoit l'appel
            new PrivateChannel('users.' . $this->caller->id),   // L'appelant reçoit la confirmation de l'initiation
        ];
    }

    /**
     * Le nom de l'événement de diffusion.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.initiated';
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
            'caller' => [
                'id' => $this->caller->id,
                'name' => $this->caller->name,
                'profile_picture' => $this->caller->profile_picture, // Assurez-vous que ce champ existe
            ],
            'call_type' => $this->callType,
        ];
    }
}
