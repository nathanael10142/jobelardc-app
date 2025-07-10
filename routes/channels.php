<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Ici vous pouvez enregistrer les canaux de diffusion de votre application
| et leur logique d’autorisation. Ces canaux sont utilisés par Laravel Echo
| pour sécuriser l’accès aux événements diffusés.
|
*/

// Canal privé pour un utilisateur spécifique
Broadcast::channel('users.{id}', function ($user, $id) {
    // Autoriser seulement si l'utilisateur authentifié correspond à l'ID du canal
    return (int) $user->id === (int) $id;
});

// Exemple de canal public (optionnel)
// Broadcast::channel('public-channel', function () {
//     return true;
// });

// Exemple de canal de groupe avec autorisation personnalisée
// Broadcast::channel('group.{groupId}', function ($user, $groupId) {
//     // Vérifiez si l'utilisateur appartient au groupe (à adapter selon votre logique)
//     return $user->groups->contains($groupId);
// });
