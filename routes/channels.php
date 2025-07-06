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

// THIS LINE IS CRUCIAL FOR THE /broadcasting/auth ENDPOINT
Broadcast::routes(); // <--- ADD THIS LINE HERE!

// Canal privé pour un utilisateur spécifique pour les appels
// Seul l'utilisateur avec l'ID correspondant peut écouter ce canal.
// Le nom du canal côté serveur doit correspondre à celui écouté côté client.
Broadcast::channel('calls.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Si vous aviez d'autres canaux, ils resteraient ici.
// Par exemple, si vous avez un canal pour les messages privés:
// Broadcast::channel('private-chat.{userId}', function (User $user, $userId) {
//     return (int) $user->id === (int) $userId;
// });