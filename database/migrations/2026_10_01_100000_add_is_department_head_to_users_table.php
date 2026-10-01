<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Responsable de son service (housekeeping, réception) : voit les OT signalés
            // par toute son équipe, sans droit de paramétrage. Sans effet pour les autres rôles.
            $table->boolean('is_department_head')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_department_head');
        });
    }
};
