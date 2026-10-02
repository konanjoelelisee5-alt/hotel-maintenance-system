<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intervention_sessions', function (Blueprint $table) {
            // Temps saisi après coup (chrono oublié) ou session corrigée : signalé au
            // responsable dans le suivi, et tracé au journal d'activité.
            $table->boolean('is_manual')->default(false)->after('duration_minutes');
            $table->timestamp('corrected_at')->nullable()->after('is_manual');
        });
    }

    public function down(): void
    {
        Schema::table('intervention_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'corrected_at']);
        });
    }
};
