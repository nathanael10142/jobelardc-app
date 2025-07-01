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
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // ID unique du message
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade'); // Clé étrangère vers la table conversations
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Clé étrangère vers la table users (l'expéditeur)
            $table->text('body')->nullable(); // Contenu du message (nullable pour les messages avec seulement des pièces jointes)
            $table->string('type')->default('text'); // Type de message (ex: 'text', 'image', 'video', 'audio')
            $table->timestamp('read_at')->nullable(); // Date/heure à laquelle le message a été lu par le destinataire(s)
            $table->timestamps(); // colonnes created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
