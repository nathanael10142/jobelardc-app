<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Call; // NOUVEAU: Import du modèle Call
use Illuminate\Support\Facades\Storage; // Pour générer les URLs des avatars

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call; // La propriété publique pour l'objet Call

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param Call $call L'objet Call complet.
     * @return void
     */
    public function __construct(Call $call)
    {
        // Charger les relations 'caller' et 'receiver' pour s'assurer qu'elles sont disponibles
        // lors de la sérialisation de l'événement pour la diffusion.
        $this->call = $call->load(['caller', 'receiver']);
    }

    /**
     * Obtenez les canaux sur lesquels l'événement doit être diffusé.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Diffuse sur le canal privé du RECEIVER pour qu'il reçoive l'appel.
        // L'appelant n'a pas besoin de recevoir cet événement via Echo car
        // la réponse API lui confirme déjà l'initiation.
        return [
            new PrivateChannel('users.' . $this->call->receiver_id),
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
        // Fonction utilitaire pour obtenir l'URL de l'avatar
        $getAvatarUrl = function ($path) {
            if (empty($path)) {
                return 'https://placehold.co/100x100/ccc/white?text=?'; // Placeholder par défaut
            }
            // Vérifie si c'est une URL externe (ex: Google avatar)
            if (Str::startsWith($path, ['http://', 'https://'])) {
                return $path;
            }
            // Sinon, c'est un chemin de stockage local
            return Storage::url($path);
        };

        return [
            'call' => [
                'uuid' => $this->call->call_uuid, // IMPORTANT: Utilise 'call_uuid' pour la cohérence
                'db_id' => $this->call->id, // L'ID auto-incrémenté de la base de données (pour info)
                'caller_id' => $this->call->caller_id,
                'receiver_id' => $this->call->receiver_id,
                'call_type' => $this->call->call_type,
                'status' => $this->call->status,
                'caller_name' => $this->call->caller->name ?? null,
                'caller_avatar' => $getAvatarUrl($this->call->caller->profile_picture ?? null), // Génère l'URL complète
                'receiver_name' => $this->call->receiver->name ?? null,
                'receiver_avatar' => $getAvatarUrl($this->call->receiver->profile_picture ?? null), // Génère l'URL complète
                // Ajoutez ici toute autre donnée de l'objet Call que le frontend pourrait avoir besoin
                'created_at' => $this->call->created_at->toDateTimeString(), // Utile pour le frontend
            ],
        ];
    }
}
