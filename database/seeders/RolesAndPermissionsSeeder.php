<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash; // Ajouté si tu crées des utilisateurs ici
use App\Models\User; // Ajouté si tu crées des utilisateurs ici

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialise les caches des permissions (important !)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Créer les Permissions
        // Utilise firstOrCreate pour éviter les erreurs si les permissions existent déjà.
        // Assure-toi que TOUTES les permissions utilisées ci-dessous sont définies ici.

        // Permissions générales pour la gestion des utilisateurs
        Permission::firstOrCreate(['name' => 'manage users']); // Pour créer, modifier, supprimer des utilisateurs
        Permission::firstOrCreate(['name' => 'view users']);   // Pour voir la liste des utilisateurs

        // Permissions pour la gestion des jobs/annonces
        Permission::firstOrCreate(['name' => 'create job']);    // Créer une annonce
        Permission::firstOrCreate(['name' => 'edit job']);      // Éditer une annonce (ses propres ou toutes)
        Permission::firstOrCreate(['name' => 'delete job']);    // Supprimer une annonce (ses propres ou toutes)
        Permission::firstOrCreate(['name' => 'view job']);      // Voir les détails d'une annonce (la permission qui manquait !)
        Permission::firstOrCreate(['name' => 'publish job']);   // Publier/dépublier une annonce (pour admin/modérateur)
        Permission::firstOrCreate(['name' => 'moderate jobs']); // Modérer le contenu des jobs (pour admin/modérateur)

        // Permissions spécifiques pour le flux de candidature
        Permission::firstOrCreate(['name' => 'apply for job']);         // Postuler à un job
        Permission::firstOrCreate(['name' => 'view own applications']); // Voir ses propres candidatures
        Permission::firstOrCreate(['name' => 'view job applications']); // Voir les candidatures reçues pour ses jobs (pour employeur/admin)

        // Permissions pour la gestion des catégories
        Permission::firstOrCreate(['name' => 'manage categories']); // Pour créer, modifier, supprimer des catégories

        // Permissions pour la gestion des transactions/paiements
        Permission::firstOrCreate(['name' => 'view transactions']);    // Voir les transactions
        Permission::firstOrCreate(['name' => 'manage transactions']);  // Gérer les transactions (pour admin)

        // Tu peux ajouter d'autres permissions au fur et à mesure que l'application grandit...
        // Ex: 'view dashboard', 'manage settings', 'send notifications', 'boost job', etc.


        // 2. Créer les Rôles et leur assigner des Permissions
        // Utilise firstOrCreate pour les rôles également.

        // Rôle Super Administrateur (peut tout faire)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all()); // Donne toutes les permissions existantes

        // Rôle Administrateur (peut gérer les utilisateurs, jobs, catégories, transactions)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'manage users',
            'view users',
            'moderate jobs',
            'publish job',
            'manage categories',
            'view transactions',
            'manage transactions',
            'view job applications', // L'admin peut voir toutes les candidatures
            'view job', // L'admin doit pouvoir voir les détails de n'importe quel job
            'create job', // L'admin peut aussi créer des jobs (optionnel)
            'edit job',   // L'admin peut éditer n'importe quel job (optionnel)
            'delete job', // L'admin peut supprimer n'importe quel job (optionnel)
        ]);

        // Rôle Modérateur (peut modérer les jobs)
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            'moderate jobs',
            'view users',
            'view transactions',
            'view job', // Le modérateur doit pouvoir voir les jobs
            'view job applications', // Le modérateur peut voir les candidatures pour les jobs modérés
        ]);

        // Rôle Candidat (chercheur d'emploi)
        $candidateRole = Role::firstOrCreate(['name' => 'candidate']);
        $candidateRole->givePermissionTo([
            'view job',             // Peut voir toutes les annonces
            'apply for job',        // Peut postuler à un job
            'view own applications',// Peut voir ses propres candidatures
            // Les permissions 'edit job' et 'delete job' ici sont risquées si elles désignent n'importe quel job.
            // Elles sont probablement destinées à l'édition/suppression de leur PROPRE profil.
            // La logique pour éditer/supprimer son PROPRE job ou profil sera gérée par Laravel Policies ou Gates,
            // en plus de la permission 'edit job'. Pour l'instant, on les garde comme tu les avais mis.
            'edit job',   // Pour éditer son profil ou ses candidatures.
            'delete job', // Pour supprimer son profil ou ses candidatures.
        ]);

        // Rôle Employeur (proposeur de job)
        $employerRole = Role::firstOrCreate(['name' => 'employer']);
        $employerRole->givePermissionTo([
            'create job',           // Peut créer des annonces
            'edit job',             // Peut éditer ses propres annonces
            'delete job',           // Peut supprimer ses propres annonces
            'view job applications',// Peut voir les candidatures à ses jobs
            'view users',           // Peut voir les profils des candidats (ceux qui ont postulé par ex.)
            'view job',             // Peut voir n'importe quelle annonce
        ]);

        // Rôle Utilisateur Standard (fallback ou rôle générique si tu ne distingues pas encore employeur/candidat à l'inscription)
        // Si tu utilises 'candidate' ou 'employer' comme rôle par défaut à l'inscription,
        // tu pourrais envisager de simplifier ce rôle 'user' ou de le supprimer si non utilisé.
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo([
            'view job', // Un utilisateur standard devrait pouvoir voir les jobs
            // Si ces permissions concernent les PROPRES jobs de l'utilisateur, elles seront gérées via Policy.
            // Sinon, ils ne devraient pas pouvoir créer/éditer/supprimer des jobs s'ils ne sont pas employeurs.
            'create job',
            'edit job',
            'delete job',
        ]);

        // Créer l'utilisateur super_admin par défaut si tu ne le fais pas dans DatabaseSeeder.php
        // (Il est mieux de le faire dans DatabaseSeeder qui appelle ce seeder)
        // Cependant, pour être complet ici si jamais tu lançais ce seeder seul:
        // if (\App\Models\User::where('email', 'nathanaelhacker6@gmail.com')->doesntExist()) {
        //     $user = \App\Models\User::create(
        //         [
        //             'name' => 'Nathanael Hacker',
        //             'email' => 'nathanaelhacker6@gmail.com',
        //             'password' => Hash::make('nathanael1209ba'),
        //             'email_verified_at' => now(),
        //             'user_type' => 'both', // Correspond à ta colonne user_type
        //             'location' => 'Goma',
        //             'phone_number' => '0891234567',
        //             'bio' => 'Super administrateur de Jobela RDC.',
        //         ]
        //     );
        //     $user->assignRole('super_admin');
        // }
    }
}
