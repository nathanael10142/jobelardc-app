<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Call; // Importe le modèle Call
use App\Models\User; // Importe le modèle User
use Illuminate\Broadcasting\PrivateChannel; // Pour les canaux privés

class CallHungUp implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;
    public $enderId; // L'ID de l'utilisateur qui a raccroché

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param Call $call L'objet Call mis à jour avec le statut 'ended', 'cancelled', 'rejected' ou 'missed'.
     * @param int $enderId L'ID de l'utilisateur qui a initié l'action de raccrocher.
     */
    public function __construct(Call $call, int $enderId)
    {
        $this->call = $call->load(['caller', 'receiver']); // Charge les relations pour l'événement
        $this->enderId = $enderId;
    }

    /**
     * Obtient les canaux sur lesquels l'événement doit être diffusé.
     *
     * Cet événement doit être diffusé sur le canal privé de l'appel spécifique
     * pour que les deux participants (celui qui a raccroché et l'autre) le reçoivent.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Diffuse sur un canal privé spécifique à l'appel, en utilisant son UUID.
        // Les deux participants de l'appel doivent écouter ce canal.
        return [
            new PrivateChannel('calls.' . $this->call->call_uuid),
            // On peut aussi diffuser sur les canaux privés des utilisateurs si nécessaire,
            // par exemple pour mettre à jour l'historique des appels en temps réel.
            new PrivateChannel('users.' . $this->call->caller_id),
            new PrivateChannel('users.' . $this->call->receiver_id),
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
            'call_uuid' => $this->call->call_uuid,
            'status' => $this->call->status, // Le statut final de l'appel (ended, cancelled, rejected, missed)
            'duration' => $this->call->duration,
            'ended_at' => $this->call->ended_at ? $this->call->ended_at->toDateTimeString() : null,
            'ender_id' => $this->enderId,
            'caller' => [
                'id' => $this->call->caller->id,
                'name' => $this->call->caller->name,
                'profile_picture' => $this->call->caller->profile_picture,
            ],
            'receiver' => [
                'id' => $this->call->receiver->id,
                'name' => $this->call->receiver->name,
                'profile_picture' => $this->call->receiver->profile_picture,
            ],
            'message' => 'L\'appel a été terminé.',
        ];
    }

    /**
     * Le nom de l'événement à diffuser.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.hung-up';
    }
}
