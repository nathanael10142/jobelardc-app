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
        Schema::table('job_listings', function (Blueprint $table) {
            // Ajoute la colonne user_id avec une clé étrangère.
            // Le `nullable()` permet aux annonces d'être postées par des "invités"
            // si vous n'êtes pas connecté, et `onDelete('set null')` gère
            // ce qui se passe si l'utilisateur associé est supprimé.
            $table->foreignId('user_id')
                  ->nullable() // Très important si un invité peut poster
                  ->constrained() // Crée une contrainte de clé étrangère vers la table 'users'
                  ->onDelete('set null') // Si l'utilisateur est supprimé, 'user_id' devient null
                  ->after('posted_by_type'); // Place la colonne après 'posted_by_type' pour l'ordre
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            // Il est crucial de d'abord supprimer la contrainte de clé étrangère
            $table->dropForeign(['user_id']);
            // Ensuite, supprimer la colonne elle-même
            $table->dropColumn('user_id');
        });
    }
};
