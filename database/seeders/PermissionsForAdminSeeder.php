<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Assurez-vous que c'est le bon chemin vers votre modèle User

class PermissionsForAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser les caches de permissions (important !)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Créer les permissions nécessaires si elles n'existent pas ---
        // Permission pour le PermissionController
        Permission::firstOrCreate(['name' => 'manage permissions']);
        // Permission pour le RoleController
        Permission::firstOrCreate(['name' => 'manage roles']);

        // --- 2. Trouver ou créer un rôle "Admin" ---
        // C'est une bonne pratique d'avoir un rôle générique pour les admins
        $adminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // --- 3. Assigner les permissions spécifiques au rôle "Admin" ---
        $adminRole->givePermissionTo('manage permissions');
        $adminRole->givePermissionTo('manage roles');

        // --- 4. Assigner le rôle "Admin" à l'utilisateur spécifique ---
        // L'email de l'utilisateur à qui assigner le rôle admin
        $targetEmail = 'nathanaelhacker6@gmail.com';
        $user = User::where('email', $targetEmail)->first();

        if ($user) {
            $user->assignRole($adminRole);
            $this->command->info("L'utilisateur {$user->email} a été assigné au rôle '{$adminRole->name}'.");
        } else {
            $this->command->warn("Utilisateur avec l'email '{$targetEmail}' non trouvé. Assurez-vous que cet utilisateur existe dans votre base de données.");
        }
    }
}
