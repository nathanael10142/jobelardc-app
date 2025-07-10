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
        Schema::table('messages', function (Blueprint $table) {
            // IMPORTANT: Assurez-vous que le nom de la colonne est 'receiver_id'
            // pour correspondre à votre relation dans le modèle User.
            $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade')->after('user_id');
            // J'ai mis after('user_id') pour un meilleur ordre, mais ce n'est pas obligatoire.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['receiver_id']);
            $table->dropColumn('receiver_id');
        });
    }
};