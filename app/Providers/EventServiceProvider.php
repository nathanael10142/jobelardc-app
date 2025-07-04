<?php

namespace App\Providers; // Cette ligne est cruciale et doit être présente !

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event; // Assurez-vous que Event est importé si vous l'utilisez
use Illuminate\Auth\Events\Registered; // Exemple d'événement
use App\Listeners\SendEmailVerificationNotification; // Exemple de listener

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class, // Exemple de listener pour l'événement Registered
        ],
        // Ajoutez vos événements et listeners personnalisés ici
        \App\Events\CallInitiated::class => [
            // \App\Listeners\HandleCallInitiated::class, // Exemple de listener si vous en avez un
        ],
        \App\Events\CallAccepted::class => [],
        \App\Events\CallRejected::class => [],
        \App\Events\CallEnded::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Laissez à false si vous listez explicitement vos événements dans $listen
    }
}
