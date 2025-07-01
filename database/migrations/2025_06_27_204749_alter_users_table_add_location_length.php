<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // N'oubliez pas d'ajouter ceci !

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Modifie la colonne 'location' pour s'assurer qu'elle peut contenir 255 caractères.
            $table->string('location', 255)->nullable()->change();

            // Si la colonne 'user_type' existe déjà, nous la supprimons d'abord.
            // Cela garantit une recréation propre pour éviter les conflits de contraintes.
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });

        // Ensuite, nous ajoutons la colonne 'user_type' avec la définition souhaitée.
        // Nous la plaçons après 'location' pour un ordre logique.
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 255)
                  ->nullable(false) // La colonne ne peut pas être nulle
                  ->default('both') // Valeur par défaut
                  ->after('location'); // Position de la colonne
        });

        // Enfin, nous ajoutons la contrainte CHECK en utilisant du SQL brut.
        // Le nom de la contrainte est spécifié explicitement pour éviter les ambiguïtés.
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_user_type_check CHECK (user_type IN ('prestataire', 'demandeur', 'both', 'employer', 'candidate'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir la colonne 'location' à sa taille par défaut ou originale.
            $table->string('location')->nullable()->change();

            // Supprimer la contrainte CHECK en premier lors de l'annulation.
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");

            // Supprimer la colonne 'user_type' si elle a été ajoutée/modifiée par cette migration.
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });
        // Si 'user_type' existait avant cette migration avec une autre définition,
        // vous devriez la recréer ici dans son état original si nécessaire.
        // Pour l'instant, nous supposons que cette migration est la source principale de sa définition.
    }
};
