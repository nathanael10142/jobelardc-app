<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\JobListing;
use App\Policies\JobListingPolicy;
use Illuminate\Support\Facades\Broadcast; // <-- AJOUTEZ CETTE LIGNE

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        JobListing::class => JobListingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies(); // Cette ligne est importante pour enregistrer les policies

        // Définition des Gates (portes d'autorisation)
        Gate::define('view-admin-dashboard', function (User $user) {
            return $user->hasAnyRole(['super_admin', 'admin']);
        });

        // C'EST LA LIGNE CRUCIALE POUR LA DIFFUSION
        Broadcast::routes(); // <-- AJOUTEZ CETTE LIGNE

        // Assurez-vous que cette ligne est présente et pointe vers votre fichier channels.php
        require base_path('routes/channels.php');
    }
}