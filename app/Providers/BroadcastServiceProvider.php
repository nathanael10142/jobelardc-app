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
        // Cette ligne est cruciale ! Elle enregistre les routes d'authentification
        // nécessaires pour vos canaux de diffusion (par exemple, pour Pusher).
        // Assurez-vous qu'elle est décommentée.
        Broadcast::routes();

        // Cette ligne inclut le fichier où vous définissez vos canaux de diffusion.
        // C'est le fichier 'routes/channels.php' que nous venons de modifier.
        require base_path('routes/channels.php');
    }
}

