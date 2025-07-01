<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobListing; // Importez le modèle JobListing
use Carbon\Carbon; // Importez Carbon pour la gestion des dates

class DeleteUnboostedListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:delete-unboosted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime les annonces non boostées après 24 heures.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = Carbon::now()->subHours(24); // Calcule l'heure il y a 24 heures

        $deletedCount = JobListing::whereNull('boosted_at') // Annonces qui n'ont jamais été boostées
                                ->where('created_at', '<', $threshold) // Créées il y a plus de 24 heures
                                ->delete(); // Supprime les annonces correspondantes

        $this->info("{$deletedCount} annonces non boostées de plus de 24 heures ont été supprimées.");
    }
}
