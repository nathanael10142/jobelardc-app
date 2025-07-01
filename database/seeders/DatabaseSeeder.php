<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Ajoute ceci pour hacher le mot de passe

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Appelle le seeder de rôles et permissions en premier
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Crée l'utilisateur de test spécifique et lui assigne un rôle
        // Utilise firstOrCreate pour éviter les erreurs de doublons si l'utilisateur existe déjà
        $nathanael = User::firstOrCreate(
            ['email' => 'nathanaelhacker6@gmail.com'], // Critère de recherche unique
            [
                'name' => 'Nathanael Hacker',
                'password' => Hash::make('nathanael1209ba'), // Hache le mot de passe
                'phone_number' => '0890000000',
                'bio' => 'Développeur et super administrateur de Jobela RDC.',
                'profile_picture' => null, // Assure que c'est null si aucune image n'est fournie
                'location' => 'Goma',
                'user_type' => 'both',
            ]
        );
        // Assigne le rôle 'super_admin' à ton utilisateur avec Spatie
        $nathanael->assignRole('super_admin');

        // 3. Crée 10 utilisateurs factices supplémentaires et assigne-leur le rôle 'user' par défaut
        // Utilise firstOrCreate pour le factory aussi, si tu veux éviter les doublons lors de re-seeding
        User::factory(10)->create()->each(function ($user) {
            $user->assignRole('user');
        });

        // 4. Appelle les autres seeders
        $this->call([
            CategorySeeder::class,
            JobSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
