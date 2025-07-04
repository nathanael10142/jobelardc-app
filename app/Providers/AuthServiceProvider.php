<?php

namespace App\Providers; // Cette ligne est cruciale et doit être présente !

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate; // Assurez-vous que Gate est importé si vous l'utilisez
use App\Models\User; // Assurez-vous que le modèle User est importé
use App\Models\JobListing; // Importez le modèle JobListing si vous avez des policies pour cela
use App\Policies\JobListingPolicy; // Importez la policy correspondante

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy', // Exemple
        JobListing::class => JobListingPolicy::class, // Exemple de mapping de policy
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Définition des Gates (portes d'autorisation)
        Gate::define('view-admin-dashboard', function (User $user) {
            return $user->hasAnyRole(['super_admin', 'admin']);
        });

        // Les autres Gates ou logiques d'autorisation peuvent être définies ici.
    }
}
