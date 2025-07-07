<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Enregistre les routes nécessaires pour l'authentification des canaux de diffusion
        // avec le middleware 'auth' pour protéger l'accès aux canaux privés/presence.
        Broadcast::routes(['middleware' => ['auth']]);

        // Charge les définitions des canaux de diffusion dans 'routes/channels.php'
        require base_path('routes/channels.php');
    }
}
