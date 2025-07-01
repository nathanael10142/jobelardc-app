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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // L'ID de l'utilisateur qui propose/demande le job
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // La catégorie du job
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2)->nullable(); // Prix suggéré, si applicable
            $table->enum('price_type', ['fixed', 'hourly', 'negotiable'])->default('negotiable'); // Type de prix
            $table->string('location'); // Lieu où le service est offert/demandé (ex: "Gombe", "Limete")
            $table->string('contact_phone')->nullable(); // Numéro de contact direct pour le job
            $table->string('contact_email')->nullable(); // Email de contact direct
            $table->boolean('is_featured')->default(false); // Pour les annonces "boostées"
            $table->timestamp('expires_at')->nullable(); // Date d'expiration de l'annonce
            $table->enum('status', ['active', 'pending', 'completed', 'cancelled'])->default('active'); // Statut de l'annonce
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
