<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request; // Importez la classe Request

class AppServiceProvider extends ServiceProvider
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
     */
    public function boot(): void
    {
        // Force l'utilisation du HTTPS si l'application est en production et derrière un proxy comme Render.
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');

            // Ajoutez cette ligne pour explicitement faire confiance aux proxies
            // Cela dit à Laravel de regarder les en-têtes X-Forwarded-* pour le protocole
            $this->app['request']->server->set('HTTPS', true); // Force la variable HTTPS du serveur à true
        }
    }
}
