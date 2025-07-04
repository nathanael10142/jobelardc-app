<?php

namespace Database\Seeders;

// Supprime l'ancien import
// use App\Models\Job;
// Ajoute le nouvel import pour ton modèle renommé
use App\Models\JobPosting; // <-- CHANGEMENT ICI

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crée 50 jobs factices en utilisant le modèle JobPosting
        JobPosting::factory(50)->create(); // <-- CHANGEMENT ICI
    }
}