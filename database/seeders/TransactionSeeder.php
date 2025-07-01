<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User; // Garder l'import si d'autres parties du seeder l'utilisent, sinon peut être retiré
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Les utilisateurs sont censés être déjà créés par le DatabaseSeeder principal.
        // Cela évite la duplication de logique et les conflits.

        // Crée 30 transactions factices
        // Les factories de transactions génèrent généralement des données uniques.
        // Si votre modèle Transaction a des contraintes d'unicité sur des champs que la factory pourrait
        // dupliquer (ce qui est rare pour des transactions génériques), vous pourriez envisager
        // une logique plus complexe ici, mais pour la plupart des cas, create() est suffisant.
        Transaction::factory(30)->create();
    }
}
