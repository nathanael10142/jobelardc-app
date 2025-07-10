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
        Schema::create('message_user_reads', function (Blueprint $table) {
            // Clé étrangère vers la table 'messages'
            $table->foreignId('message_id')->constrained()->onDelete('cascade');

            // Clé étrangère vers la table 'users'
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Colonne pour enregistrer quand le message a été lu par cet utilisateur
            $table->timestamp('read_at')->nullable();

            // Définir une clé primaire composite pour éviter les doublons
            $table->primary(['message_id', 'user_id']);

            // Ajoute created_at et updated_at (pour savoir quand l'entrée a été créée/mise à jour)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_user_reads');
    }
};