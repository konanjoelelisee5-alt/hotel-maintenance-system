<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Ces deux champs déterminent à quels OT cette politique s'applique.
            // S'ils sont NULL, la politique s'applique à tous (politique "par défaut")
            $table->enum('priority', ['basse', 'moyenne', 'haute', 'urgente'])->nullable();
            $table->enum('work_order_type', ['maintenance', 'demande_client', 'preventif'])->nullable();

            // Délai avant qu'une première action soit attendue (ex: démarrage intervention)
            $table->unsignedInteger('response_time_minutes');

            // Délai avant que l'OT entier doive être résolu
            $table->unsignedInteger('resolution_time_minutes');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};