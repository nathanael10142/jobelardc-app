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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Candidat
            $table->foreignId('job_post_id')->constrained('job_posts')->onDelete('cascade'); // Offre d'emploi
            $table->string('status')->default('pending'); // pending, reviewed, accepted, rejected
            $table->longText('cover_letter')->nullable();
            $table->string('resume_path')->nullable(); // Chemin vers le fichier CV (stocké par exemple dans storage/app/public/resumes)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
