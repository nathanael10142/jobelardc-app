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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->unique()->nullable(); // Numéro de téléphone pour M-Pesa/Orange Money
            $table->text('bio')->nullable(); // Petite description du profil
            $table->string('profile_picture')->nullable(); // Chemin vers la photo de profil
            $table->string('location')->nullable(); // Ville/quartier de l'utilisateur
            $table->enum('user_type', ['prestataire', 'demandeur', 'both'])->default('both'); // Type d'utilisateur
            $table->rememberToken();
            $table->timestamps(); // created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
