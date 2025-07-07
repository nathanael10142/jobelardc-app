<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use Illuminate\Support\Facades\Log; // <-- Ajoutez cette ligne

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
    // Ajout de logs pour le débogage
    Log::info("Authorization attempt for channel 'calls.{$userId}'.");
    if ($user) {
        Log::info("Authenticated user ID: {$user->id}. Channel ID: {$userId}.");
        return (int) $user->id === (int) $userId;
    } else {
        Log::warning("No authenticated user found for channel 'calls.{$userId}'.");
        return false; // L'utilisateur n'est pas authentifié
    }
});

// Autres canaux possibles...