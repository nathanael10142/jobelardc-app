<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique(); // Assurez-vous d'avoir un slug unique
            $table->longText('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->string('job_type'); // ex: full-time, part-time, contract
            $table->string('location');
            $table->boolean('is_remote')->default(false);
            $table->timestamp('application_deadline');
            $table->string('status')->default('active'); // ex: active, inactive, closed
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps(); // created_at et updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
