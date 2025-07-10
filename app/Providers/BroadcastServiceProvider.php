<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Définir les routes de broadcasting avec protection middleware auth:sanctum (ou 'auth' selon ton système)
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Charger les définitions des canaux dans routes/channels.php
        require base_path('routes/channels.php');
    }
}
