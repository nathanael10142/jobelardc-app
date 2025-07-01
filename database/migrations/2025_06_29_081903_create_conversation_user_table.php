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
        Schema::create('conversation_user', function (Blueprint $table) {
            // Clé étrangère vers la table conversations
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            // Clé étrangère vers la table users
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Définir une clé primaire composée pour assurer l'unicité de chaque paire conversation-utilisateur
            $table->primary(['conversation_id', 'user_id']);

            // Vous pouvez ajouter d'autres colonnes ici si nécessaire, par exemple:
            // $table->timestamp('last_read_at')->nullable(); // La dernière fois que cet utilisateur a lu la conversation
            // $table->boolean('is_muted')->default(false); // Si l'utilisateur a coupé le son de la conversation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};
