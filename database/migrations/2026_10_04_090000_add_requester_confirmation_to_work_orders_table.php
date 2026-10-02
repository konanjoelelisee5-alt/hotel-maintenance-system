<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Le service demandeur confirme que la panne est bien réglée (ou rouvre l'OT).
            $table->timestamp('requester_confirmed_at')->nullable()->after('completed_at');
            $table->foreignId('requester_confirmed_by')->nullable()->after('requester_confirmed_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requester_confirmed_by');
            $table->dropColumn('requester_confirmed_at');
        });
    }
};
