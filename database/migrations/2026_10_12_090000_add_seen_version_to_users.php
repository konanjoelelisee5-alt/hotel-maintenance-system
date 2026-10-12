<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dernière version dont la personne a ouvert la page « Nouveautés » : le point bleu
 * du lien disparaît jusqu'à la version suivante (App\Support\AppVersion::hasUnseen).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('seen_version', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('seen_version');
        });
    }
};
