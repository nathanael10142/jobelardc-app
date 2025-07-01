<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\User; // Garder l'import si d'autres parties du seeder l'utilisent, sinon peut être retiré
use App\Models\Category; // Garder l'import si d'autres parties du seeder l'utilisent, sinon peut être retiré
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Les utilisateurs et les catégories sont censés être déjà créés
        // par le DatabaseSeeder principal qui appelle RolesAndPermissionsSeeder
        // et CategorySeeder. Cela évite la duplication de logique et les conflits.

        // Crée 50 jobs factices
        // Les factories de jobs génèrent généralement des données uniques pour la plupart des champs.
        // Si votre modèle Job a des contraintes d'unicité sur des champs que la factory pourrait
        // dupliquer (ce qui est rare pour des jobs génériques), vous pourriez envisager
        // une logique plus complexe ici, mais pour la plupart des cas, create() est suffisant.
        Job::factory(50)->create();
    }
}
