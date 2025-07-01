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
            // Ajoute une colonne 'google_id' de type chaîne, qui peut être nulle
            // après la colonne 'email' pour un bon ordre.
            $table->string('google_id')->nullable()->after('email');
            // Optionnel: si tu veux stocker l'URL de l'avatar Google
            $table->string('profile_picture')->nullable()->change(); // Assure-toi que profile_picture est déjà de type string et nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprime la colonne 'google_id' si on annule la migration
            $table->dropColumn('google_id');
            // Optionnel: Revertir le changement de profile_picture si nécessaire
            // $table->string('profile_picture')->nullable(false)->change(); // Exemple si tu veux le rendre non-nullable à nouveau
        });
    }
};
