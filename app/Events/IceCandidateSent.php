<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel; // Pour les canaux privés

class IceCandidateSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callUuid;
    public $senderId;
    public $receiverId;
    public $candidate; // Le candidat ICE

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param string $callUuid L'UUID unique de l'appel.
     * @param int $senderId L'ID de l'utilisateur qui envoie le candidat ICE.
     * @param int $receiverId L'ID de l'utilisateur qui doit recevoir le candidat ICE.
     * @param array $candidate Le payload du candidat ICE.
     */
    public function __construct(string $callUuid, int $senderId, int $receiverId, array $candidate)
    {
        $this->callUuid = $callUuid;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->candidate = $candidate;
    }

    /**
     * Obtient les canaux sur lesquels l'événement doit être diffusé.
     *
     * Les candidats ICE sont échangés entre les deux participants.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Diffuse sur le canal privé de l'utilisateur destinataire.
        // Alternativement, on pourrait utiliser un canal `calls.{call_uuid}` si les deux sont abonnés.
        return [
            new PrivateChannel('users.' . $this->receiverId),
            // Ou si le frontend écoute un canal d'appel spécifique :
            // new PrivateChannel('calls.' . $this->callUuid),
        ];
    }

    /**
     * Les données à diffuser avec l'événement.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'call_uuid' => $this->callUuid,
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'type' => 'ice-candidate', // Type de signalisation
            'payload' => $this->candidate,
        ];
    }

    /**
     * Le nom de l'événement à diffuser.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'webrtc.ice-candidate';
    }
}
