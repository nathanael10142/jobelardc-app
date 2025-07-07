<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🌐 Middleware web
        $middleware->web(append: [
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class, // ✅ CORS pour les requêtes web (Broadcasting, Sanctum)
        ]);

        // 📡 Middleware API
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class, // ✅ Indispensable aussi ici
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // ✅ Pour les cookies Sanctum
        ]);

        // 🛡️ Aliases personnalisés (Spatie)
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        App\Providers\RouteServiceProvider::class,
        // Spatie Permission auto-chargé si bien installé, sinon décommente ci-dessous :
        // Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->create();
