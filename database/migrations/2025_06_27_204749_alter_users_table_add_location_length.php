<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Modifie la colonne 'location' pour s'assurer qu'elle peut contenir 255 caractères.
            // Si elle était déjà de 255 caractères par défaut, cette ligne ne changera rien,
            // mais elle garantit qu'elle ne soit pas tronquée si une ancienne migration
            // l'avait définie avec une taille plus courte.
            $table->string('location', 255)->nullable()->change();

            // Modifie la colonne 'user_type' pour inclure les nouvelles valeurs
            // 'employer' et 'candidate' dans l'énumération.
            // IMPORTANT : Cette opération peut nécessiter une recréation de la colonne
            // en base de données si le type de base de données ne supporte pas
            // la modification directe des ENUM.
            // En développement, l'utilisation de `php artisan migrate:fresh` est souvent
            // la méthode la plus simple pour appliquer ce type de changement.
            $table->enum('user_type', ['prestataire', 'demandeur', 'both', 'employer', 'candidate'])
                  ->default('both')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir la colonne 'location' à sa taille par défaut ou originale.
            // Par défaut, `string()` sans longueur spécifiée utilise 255 caractères.
            $table->string('location')->nullable()->change();

            // Revertir la colonne 'user_type' à ses valeurs originales.
            // Attention : Si des données ont été insérées avec 'employer' ou 'candidate',
            // cette reversion pourrait échouer ou tronquer ces données.
            $table->enum('user_type', ['prestataire', 'demandeur', 'both'])
                  ->default('both')
                  ->change();
        });
    }
};
