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
        // Utilise firstOrCreate pour éviter les doublons lors des exécutions multiples
        Permission::firstOrCreate(['name' => 'manage permissions']);
        Permission::firstOrCreate(['name' => 'manage roles']);
        // Ajoutez ici toutes les autres permissions que vous avez définies dans RolesAndPermissionsSeeder
        // pour vous assurer qu'elles existent avant d'être attribuées.
        // Par exemple:
        Permission::firstOrCreate(['name' => 'manage users']);
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
        Permission::firstOrCreate(['name' => 'create job listings']);
        Permission::firstOrCreate(['name' => 'edit job listings']);
        Permission::firstOrCreate(['name' => 'delete job listings']);
        Permission::firstOrCreate(['name' => 'view job listings']);
        Permission::firstOrCreate(['name' => 'apply for jobs']);
        Permission::firstOrCreate(['name' => 'manage applications']);
        Permission::firstOrCreate(['name' => 'manage chats']);
        Permission::firstOrCreate(['name' => 'access admin panel']);


        // --- 2. Trouver ou créer un rôle "super_admin" ---
        // C'est le rôle qui aura les permissions d'administration
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // --- 3. Assigner les permissions spécifiques au rôle "super_admin" ---
        // Donnez toutes les permissions au rôle super_admin, comme dans RolesAndPermissionsSeeder
        $superAdminRole->givePermissionTo(Permission::all());

        // --- 4. Assigner le rôle "super_admin" à l'utilisateur spécifique ---
        // L'email de l'utilisateur à qui assigner le rôle admin
        $targetEmail = 'nathanaelhacker6@gmail.com';
        $user = User::where('email', $targetEmail)->first();

        if ($user) {
            // Assurez-vous que l'utilisateur a bien le rôle 'super_admin'
            if (!$user->hasRole('super_admin')) {
                $user->assignRole($superAdminRole);
                $this->command->info("L'utilisateur {$user->email} a été assigné au rôle '{$superAdminRole->name}'.");
            } else {
                $this->command->info("L'utilisateur {$user->email} a déjà le rôle '{$superAdminRole->name}'.");
            }
        } else {
            $this->command->warn("Utilisateur avec l'email '{$targetEmail}' non trouvé. Assurez-vous que cet utilisateur existe dans votre base de données.");
        }
    }
}
