<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Numéro pour les alertes d'astreinte (SMS / WhatsApp / appel).
            $table->string('phone', 30)->nullable()->after('email');
            // Distingue, parmi les admins et managers, ceux qui pilotent la maintenance
            // (chef de maintenance) de ceux qui n'ont pas à être réveillés (informatique).
            $table->boolean('receives_maintenance_alerts')->default(true)->after('is_department_head');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'receives_maintenance_alerts']);
        });
    }
};
