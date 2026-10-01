<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un OT ne se supprime plus : il s'annule (doublon, fausse alerte), avec un
        // motif dans l'historique. Même approche portable que l'ajout de "rejete".
        Schema::table('work_orders', function (Blueprint $table) {
            $table->enum('status', ['ouvert', 'en_cours', 'en_attente', 'resolu', 'ferme', 'rejete', 'annule'])
                ->default('ouvert')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->enum('status', ['ouvert', 'en_cours', 'en_attente', 'resolu', 'ferme', 'rejete'])
                ->default('ouvert')
                ->change();
        });
    }
};
