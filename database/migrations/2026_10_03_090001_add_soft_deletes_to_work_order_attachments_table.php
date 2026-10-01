<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une photo « avant / après » est une preuve : « Supprimer » la retire de la fiche
 * mais garde la ligne et le fichier, avec la trace de qui l'a retirée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_attachments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_attachments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
