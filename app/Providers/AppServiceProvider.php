<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // N'oubliez pas d'importer la façade URL

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
        // Cela garantit que toutes les URL générées par Laravel (y compris les actions de formulaire et les redirections)
        // utiliseront le protocole HTTPS, résolvant les problèmes de "Mixed Content".
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
