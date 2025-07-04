<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
// use App\Listeners\SendEmailVerificationNotification; // Vous pouvez commenter ou supprimer cette ligne

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            // SendEmailVerificationNotification::class, // Cette ligne a été supprimée/commentée pour désactiver l'envoi de l'e-mail de vérification
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
