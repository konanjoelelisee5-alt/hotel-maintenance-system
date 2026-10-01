<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nouvelle cible "astreinte" : l'équipe de garde à l'instant de l'escalade
        // (managers le jour, admins maintenance la nuit). Même approche portable
        // que l'ajout du statut "rejete" sur work_orders.
        Schema::table('escalation_rules', function (Blueprint $table) {
            $table->enum('notify_target', ['technicien_assigne', 'manager', 'admin', 'astreinte'])
                ->default('manager')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('escalation_rules', function (Blueprint $table) {
            $table->enum('notify_target', ['technicien_assigne', 'manager', 'admin'])
                ->default('manager')
                ->change();
        });
    }
};
