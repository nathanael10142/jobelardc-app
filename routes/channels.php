<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation; // Assurez-vous que ce chemin est correct
use App\Models\User; // Assurez-vous que ce chemin est correct si vous l'utilisez ailleurs

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privé pour les utilisateurs individuels (utilisé pour les appels, etc.)
// Cette logique est probablement déjà fonctionnelle puisque les appels marchent.
Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Canal privé pour les conversations
// L'utilisateur doit être authentifié ET être un participant de la conversation.
Broadcast::channel('conversations.{conversationId}', function ($user, $conversationId) {
    // Charger la conversation avec ses participants (users) pour la vérification
    $conversation = Conversation::with('users')->find($conversationId);

    // Vérifier si la conversation existe et si l'utilisateur authentifié est l'un de ses participants
    if ($conversation && $conversation->users->contains($user->id)) {
        return true; // L'utilisateur est autorisé
    }

    // Si l'utilisateur n'est pas un participant ou si la conversation n'existe pas
    return false;
});

