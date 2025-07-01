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
        // Utilise firstOrCreate pour éviter les erreurs de doublons sur le nom ou le slug
        Category::firstOrCreate(
            ['name' => 'Coiffure'],
            ['slug' => 'coiffure', 'description' => 'Services de coiffure à domicile ou en salon.']
        );
        Category::firstOrCreate(
            ['name' => 'Ménage'],
            ['slug' => 'menage', 'description' => 'Services de nettoyage et entretien.']
        );
        Category::firstOrCreate(
            ['name' => 'Réparation Électronique'],
            ['slug' => 'reparation-electronique', 'description' => 'Réparation de téléphones, ordinateurs, etc.']
        );
        Category::firstOrCreate(
            ['name' => 'Cours Particuliers'],
            ['slug' => 'cours-particuliers', 'description' => 'Soutien scolaire et cours privés.']
        );
        Category::firstOrCreate(
            ['name' => 'Plomberie'],
            ['slug' => 'plomberie', 'description' => 'Installation et réparation de plomberie.']
        );
        Category::firstOrCreate(
            ['name' => 'Électricité'],
            ['slug' => 'electricite', 'description' => 'Travaux et dépannage électrique.']
        );
        Category::firstOrCreate(
            ['name' => 'Jardinage'],
            ['slug' => 'jardinage', 'description' => 'Entretien de jardins et espaces verts.']
        );
        Category::firstOrCreate(
            ['name' => 'Couture'],
            ['slug' => 'couture', 'description' => 'Confection et retouches de vêtements.']
        );
        Category::firstOrCreate(
            ['name' => 'Transport / Livraison'],
            ['slug' => 'transport-livraison', 'description' => 'Services de transport de colis ou de personnes.']
        );
        Category::firstOrCreate(
            ['name' => 'Cuisine / Traiteur'],
            ['slug' => 'cuisine-traiteur', 'description' => 'Préparation de repas et services traiteur.']
        );

        // Crée 20 catégories factices supplémentaires
        // Nous allons boucler et utiliser firstOrCreate pour chaque catégorie factice
        // afin d'éviter les violations de contrainte unique si les noms générés par la factory
        // ne sont pas toujours uniques ou si le seeder est exécuté plusieurs fois.
        Category::factory(20)->make()->each(function ($category) {
            Category::firstOrCreate(
                ['name' => $category->name],
                [
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon, // Assurez-vous que l'icône est gérée si elle est nullable
                ]
            );
        });
    }
}
