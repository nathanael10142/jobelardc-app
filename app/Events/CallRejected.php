<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Call; // NOUVEAU: Import du modèle Call
use Illuminate\Support\Facades\Storage; // Pour générer les URLs des avatars
use Illuminate\Support\Str; // Pour la fonction Str::startsWith

class CallRejected implements ShouldBroadcast
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
        // Diffuser sur les canaux privés de l'appelant et du destinataire.
        // L'appelant doit être notifié du rejet, et le destinataire (qui a rejeté)
        // peut aussi avoir besoin d'une confirmation côté client.
        return [
            new PrivateChannel('users.' . $this->call->caller_id),    // L'appelant est notifié du rejet
            new PrivateChannel('users.' . $this->call->receiver_id), // Le destinataire (qui a rejeté)
            // Il peut aussi être utile de diffuser sur le canal de l'appel spécifique
            // pour toute logique liée à l'état de la session WebRTC.
            new PrivateChannel('calls.' . $this->call->call_uuid),
        ];
    }

    /**
     * Le nom de l'événement de diffusion.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.rejected';
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
                'status' => $this->call->status, // Devrait être 'rejected'
                'caller_name' => $this->call->caller->name ?? null,
                'caller_avatar' => $getAvatarUrl($this->call->caller->profile_picture ?? null), // Génère l'URL complète
                'receiver_name' => $this->call->receiver->name ?? null,
                'receiver_avatar' => $getAvatarUrl($this->call->receiver->profile_picture ?? null), // Génère l'URL complète
                'ended_at' => $this->call->ended_at ? $this->call->ended_at->toDateTimeString() : null, // Ajout de la date de fin
                'message' => 'L\'appel a été rejeté.', // Message informatif
            ],
        ];
    }
}
