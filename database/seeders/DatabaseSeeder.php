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
        $nathanael = User::create([
            'name' => 'Nathanael Hacker',
            'email' => 'nathanaelhacker6@gmail.com',
            'password' => Hash::make('nathanael1209ba'),
            'phone_number' => '0890000000',
            'bio' => 'Développeur et super administrateur de Jobela RDC.',
            'profile_picture' => null,
            'location' => 'Goma',
            'user_type' => 'both',
            // La colonne 'role' de ta table users est maintenant redondante avec Spatie,
            // mais tu peux la garder pour d'autres usages si tu veux. Spatie gère les rôles séparément.
            // 'role' => 'admin', // Cette ligne n'est plus gérée par Spatie.
        ]);
        // Assigne le rôle 'super_admin' à ton utilisateur avec Spatie
        $nathanael->assignRole('super_admin');

        // 3. Crée 10 utilisateurs factices supplémentaires et assigne-leur le rôle 'user' par défaut
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
