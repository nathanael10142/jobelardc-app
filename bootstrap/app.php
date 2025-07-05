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
        // AJOUTEZ CETTE LIGNE POUR ENREGISTRER TrustProxies au groupe web
        $middleware->web(append: [
            \App\Http\Middleware\TrustProxies::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Le Handler.php est configuré ici dans Laravel 11.
        // Si vous avez recréé app/Exceptions/Handler.php, il sera utilisé.
    })
    // AJOUT DE LA SECTION withProviders POUR ENREGISTRER LE RouteServiceProvider
    ->withProviders([
        App\Providers\RouteServiceProvider::class, // Enregistre votre RouteServiceProvider
        // Ajoutez ici d'autres Service Providers si nécessaire, par exemple :
        // \Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->create();