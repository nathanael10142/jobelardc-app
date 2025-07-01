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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id(); // Auto-incrément, clé primaire
            $table->string('title'); // Titre de l'annonce (ex: Plombier qualifié)
            $table->string('location'); // Emplacement (ex: Goma, Kinshasa)
            $table->string('salary')->nullable(); // Salaire ou prix (peut être nul)
            $table->text('description')->nullable(); // Description détaillée de l'annonce
            $table->string('posted_by_name'); // Nom de la personne/entreprise qui a posté
            $table->string('posted_by_type'); // Type de posteur (ex: Particulier, Entreprise)
            $table->timestamps(); // Ajoute `created_at` et `updated_at` automatiquement
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
