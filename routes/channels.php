<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use Illuminate\Support\Facades\Log;

Broadcast::channel('calls.{userId}', function (User $user, $userId) {
    Log::info("Authorization attempt for channel 'calls.{$userId}'.");
    if ($user) {
        Log::info("Authenticated user ID: {$user->id}. Channel ID: {$userId}.");
    } else {
        Log::warning("No authenticated user found for channel 'calls.{$userId}'.");
    }
    // Ne bloque jamais, toujours autorisé
    return true;
});
