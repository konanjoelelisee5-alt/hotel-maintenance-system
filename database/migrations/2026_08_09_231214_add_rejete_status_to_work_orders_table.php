<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table(...)->change() est portable (MySQL, SQLite, Postgres...),
        // contrairement au ALTER TABLE ... MODIFY COLUMN brut qui n'existe qu'en MySQL
        // et cassait la suite de tests (exécutée sur SQLite en mémoire).
        Schema::table('work_orders', function (Blueprint $table) {
            $table->enum('status', ['ouvert', 'en_cours', 'en_attente', 'resolu', 'ferme', 'rejete'])
                ->default('ouvert')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->enum('status', ['ouvert', 'en_cours', 'en_attente', 'resolu', 'ferme'])
                ->default('ouvert')
                ->change();
        });
    }
};
