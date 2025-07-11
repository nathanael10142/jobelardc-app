<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;

    /**
     * Crée une nouvelle instance d'événement.
     *
     * @param \App\Models\Message $message Le message qui a été envoyé.
     * @param \App\Models\Conversation|null $conversation La conversation associée (optionnel).
     */
    public function __construct(Message $message, Conversation $conversation = null)
    {
        $this->message = $message;
        $this->conversation = $conversation;
        // Charge la relation 'user' du message si elle n'est pas déjà chargée.
        // C'est important pour que les détails de l'expéditeur soient disponibles dans le frontend.
        $this->message->loadMissing('user');
    }

    /**
     * Obtient les canaux sur lesquels l'événement doit être diffusé.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // L'événement sera diffusé sur le canal privé de la conversation.
        // Cela garantit que seuls les participants de cette conversation recevront le message.
        return [
            new PrivateChannel('conversations.' . $this->message->conversation_id),
        ];
    }

    /**
     * Le nom de diffusion de l'événement.
     * Ce nom sera utilisé côté frontend pour écouter l'événement (par exemple, .listen('MessageSent', ...)).
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Obtient les données à diffuser avec l'événement.
     * Ces données seront envoyées au frontend.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // Nous envoyons le message complet sous forme de tableau,
        // y compris la relation 'user' qui a été chargée dans le constructeur.
        return [
            'message' => $this->message->toArray(),
        ];
    }
}
