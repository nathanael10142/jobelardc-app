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
use Illuminate\Support\Facades\Storage; // Pour générer les URLs des avatars
use Illuminate\Support\Str; // Pour la fonction Str::startsWith

class CallSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $callUuid; // Renommé de $callId à $callUuid pour la cohérence
    public $sender;
    public $receiver;
    public $type; // 'offer', 'answer', 'ice-candidate'
    public $payload; // Les données SDP ou ICE Candidate

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param string $callUuid L'UUID unique de l'appel.
     * @param User $sender L'utilisateur qui envoie le signal.
     * @param User $receiver L'utilisateur qui doit recevoir le signal.
     * @param string $type Le type de signal WebRTC.
     * @param array $payload Les données du signal.
     * @return void
     */
    public function __construct(string $callUuid, User $sender, User $receiver, string $type, array $payload)
    {
        $this->callUuid = $callUuid;
        // Charger les relations pour s'assurer que les données sont disponibles si besoin pour broadcastWith
        // sansRelations() est bon si vous voulez éviter de charger toutes les relations de l'utilisateur
        $this->sender = $sender->withoutRelations();
        $this->receiver = $receiver->withoutRelations();
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
        // CRUCIAL: Utiliser le canal 'users.' pour être cohérent avec les écouteurs JS
        // Il peut aussi être utile de diffuser sur le canal de l'appel spécifique
        // pour toute logique liée à l'état de la session WebRTC.
        return [
            new PrivateChannel('users.' . $this->receiver->id),
            new PrivateChannel('calls.' . $this->callUuid), // Ajout du canal spécifique à l'appel
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
            'signal' => [ // Encapsuler les données du signal sous une clé 'signal'
                'call_uuid' => $this->callUuid, // IMPORTANT: Utilise 'call_uuid' pour la cohérence
                'sender_id' => $this->sender->id,
                'sender_name' => $this->sender->name ?? null,
                'sender_avatar' => $getAvatarUrl($this->sender->profile_picture ?? null),
                'receiver_id' => $this->receiver->id,
                'receiver_name' => $this->receiver->name ?? null,
                'receiver_avatar' => $getAvatarUrl($this->receiver->profile_picture ?? null),
                'type' => $this->type,
                'payload' => $this->payload,
            ],
        ];
    }
}
