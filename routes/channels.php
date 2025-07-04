<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User; // Assurez-vous d'importer le modèle User

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given class instance will be automatically
| bound to the channel and the user authenticating the request.
|
*/

// Canal privé pour un utilisateur spécifique
// Seul l'utilisateur avec l'ID correspondant peut écouter ce canal.
Broadcast::channel('users.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});
