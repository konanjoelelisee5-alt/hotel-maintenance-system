<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Quel type de dépassement déclenche cette règle
            $table->enum('trigger_type', ['reponse_proche', 'reponse_depassee', 'resolution_proche', 'resolution_depassee']);

            // À combien de minutes AVANT (ou APRÈS) l'échéance cette règle se déclenche-t-elle ?
            // Positif = avant l'échéance (alerte préventive), négatif = après (retard déjà constaté)
            $table->integer('offset_minutes')->default(0);

            // Qui prévenir : le technicien assigné, ou un rôle entier (tous les managers, par ex.)
            $table->enum('notify_target', ['technicien_assigne', 'manager', 'admin'])->default('manager');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};