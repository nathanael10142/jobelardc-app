<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports.
|
*/

// Canal privé pour les appels (WebRTC, etc.)
Broadcast::channel('calls.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Autres canaux possibles
// Broadcast::channel('private-chat.{userId}', function (User $user, $userId) {
//     return (int) $user->id === (int) $userId;
// });
