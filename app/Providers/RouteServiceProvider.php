<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/'; // Assurez-vous que cette ligne est 'public const HOME = '/';'

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // COMMENTEZ OU SUPPRIMEZ LES LIGNES SUIVANTES SI VOUS N'UTILISEZ PAS routes/api.php
            // Route::middleware('api')
            //     ->prefix('api')
            //     ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Vous pouvez ajouter ici la configuration de vos limiteurs de débit
        // Par exemple, pour l'API :
        // RateLimiter::for('api', function (Request $request) {
        //     return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });
    }
}
