<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crée quelques catégories spécifiques pour Jobela RDC
        Category::create(['name' => 'Coiffure', 'slug' => 'coiffure', 'description' => 'Services de coiffure à domicile ou en salon.']);
        Category::create(['name' => 'Ménage', 'slug' => 'menage', 'description' => 'Services de nettoyage et entretien.']);
        Category::create(['name' => 'Réparation Électronique', 'slug' => 'reparation-electronique', 'description' => 'Réparation de téléphones, ordinateurs, etc.']);
        Category::create(['name' => 'Cours Particuliers', 'slug' => 'cours-particuliers', 'description' => 'Soutien scolaire et cours privés.']);
        Category::create(['name' => 'Plomberie', 'slug' => 'plomberie', 'description' => 'Installation et réparation de plomberie.']);
        Category::create(['name' => 'Électricité', 'slug' => 'electricite', 'description' => 'Travaux et dépannage électrique.']);
        Category::create(['name' => 'Jardinage', 'slug' => 'jardinage', 'description' => 'Entretien de jardins et espaces verts.']);
        Category::create(['name' => 'Couture', 'slug' => 'couture', 'description' => 'Confection et retouches de vêtements.']);
        Category::create(['name' => 'Transport / Livraison', 'slug' => 'transport-livraison', 'description' => 'Services de transport de colis ou de personnes.']);
        Category::create(['name' => 'Cuisine / Traiteur', 'slug' => 'cuisine-traiteur', 'description' => 'Préparation de repas et services traiteur.']);

        // Crée 20 catégories factices supplémentaires
        Category::factory(20)->create();
    }
}
