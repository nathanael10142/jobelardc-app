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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // L'utilisateur à l'origine de la transaction
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('set null'); // Le job concerné par la transaction (si c'est un boost)
            $table->string('transaction_id')->unique(); // ID de la transaction du fournisseur de paiement (Orange Money, M-Pesa)
            $table->decimal('amount', 10, 2); // Montant de la transaction
            $table->string('currency')->default('CDF'); // Monnaie (CDF, USD)
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending'); // Statut de la transaction
            $table->string('payment_method'); // Ex: 'Orange Money', 'M-Pesa'
            $table->string('description')->nullable(); // Description de la transaction (ex: "Boost d'annonce")
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
