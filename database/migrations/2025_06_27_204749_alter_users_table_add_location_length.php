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
        });

        // Modification de 'user_type' pour être compatible avec PostgreSQL.
        // D'abord, on s'assure que la colonne est un simple VARCHAR.
        // Si elle n'existe pas ou est d'un autre type, cela la changera en string.
        // Si elle existe déjà et que vous voulez la modifier, 'change()' est correct.
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 255)
                  ->nullable(false) // ou ->setNotnull(true) si nécessaire, en fonction de votre besoin initial
                  ->default('both')
                  ->change(); // Si la colonne existe déjà et que vous la modifiez
        });

        // Ensuite, nous ajoutons la contrainte CHECK manuellement via SQL brute pour PostgreSQL.
        // Cela évite la syntaxe que Laravel génère par défaut et qui pose problème.
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_user_type CHECK (user_type IN ('prestataire', 'demandeur', 'both', 'employer', 'candidate'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir la colonne 'location' à sa taille par défaut ou originale.
            // Par défaut, `string()` sans longueur spécifiée utilise 255 caractères.
            $table->string('location')->nullable()->change(); // Assurez-vous que c'est le comportement inverse souhaité

            // Pour annuler la modification de 'user_type' :
            // 1. Supprimez la contrainte CHECK d'abord
            // 2. Ensuite, modifiez le type de colonne si nécessaire ou supprimez-la.

            // Revertir la colonne 'user_type' à ses valeurs originales.
            // Nous allons d'abord supprimer la contrainte CHECK.
        });

        // Supprimer la contrainte CHECK lors de l'annulation de la migration.
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_user_type");

        // Si la migration précédente définissait 'user_type' avec un ensemble de valeurs différent,
        // vous pouvez essayer de le revenir à un string simple si 'dropColumn' n'est pas approprié.
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 255)
                  ->nullable(false)
                  ->default('both') // Ou l'ancienne valeur par défaut
                  ->change(); // Revertir à un simple string si besoin, sans la contrainte
        });
        // Si cette migration ajoutait la colonne, alors on ferait $table->dropColumn('user_type');
    }
};
