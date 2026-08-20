<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('due_date');
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('scheduled_at');
            $table->foreignId('scheduled_by')->nullable()->after('estimated_duration_minutes')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['scheduled_by']);
            $table->dropColumn(['scheduled_at', 'estimated_duration_minutes', 'scheduled_by']);
        });
    }
};