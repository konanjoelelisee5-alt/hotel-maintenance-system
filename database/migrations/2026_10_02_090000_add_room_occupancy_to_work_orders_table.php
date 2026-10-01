<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Occupation de la chambre déclarée par l'agent au signalement (l'application
            // n'est pas reliée à Opera) : libre, client_absent, client_present, depart.
            $table->string('room_occupancy', 20)->nullable()->after('room_id');
            // Réception déjà prévenue qu'un client risque de retrouver sa chambre en panne.
            $table->timestamp('reception_alerted_at')->nullable()->after('room_occupancy');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['room_occupancy', 'reception_alerted_at']);
        });
    }
};
