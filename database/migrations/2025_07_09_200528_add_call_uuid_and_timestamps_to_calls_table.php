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
        Schema::table('calls', function (Blueprint $table) {
            // Renommer et changer le type de 'call_id' en 'call_uuid'
            $table->renameColumn('call_id', 'call_uuid');
            // Note: Changer le type de colonne directement peut être complexe avec SQLite ou MySQL < 8.0.
            // Une approche plus robuste pourrait être de drop la colonne et de la recréer.
            // Pour la simplicité ici, on assume que la conversion est possible ou que la table est vide.
            // Si vous avez des problèmes, il faudra peut-être faire:
            // $table->dropColumn('call_uuid');
            // $table->uuid('call_uuid')->unique()->after('id');
            // Ou utiliser DB::statement pour des types spécifiques.
            // Pour le moment, on va juste s'assurer que le nom est correct, le type est géré par la base de données.
            // Si vous n'avez pas encore de données, le plus simple est de faire un migrate:fresh.


            // Si vous voulez vraiment changer le type en UUID, et que votre base de données le supporte bien,
            // vous devriez peut-être faire ceci si vous avez déjà des données:
            // 1. Ajouter une nouvelle colonne 'new_call_uuid' de type uuid
            // 2. Migrer les données de 'call_id' vers 'new_call_uuid'
            // 3. Supprimer 'call_id'
            // 4. Renommer 'new_call_uuid' en 'call_uuid'

            // Pour l'instant, si 'call_id' est déjà une chaîne unique, on va juste s'assurer que le nom est correct.
            // Si vous n'avez pas de données, le mieux est de modifier la migration initiale comme ci-dessus
            // et de faire un migrate:fresh.

            // Si vous avez déjà des données et que vous voulez vraiment le type UUID,
            // il faudrait une migration plus complexe:
            // $table->dropColumn('call_id');
            // $table->uuid('call_uuid')->unique()->after('id');


            // Ajout des nouvelles colonnes
            $table->timestamp('started_at')->nullable()->after('duration');
            $table->timestamp('ended_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'ended_at']);
            // Renommer 'call_uuid' en 'call_id' si nécessaire
            $table->renameColumn('call_uuid', 'call_id');
        });
    }
};