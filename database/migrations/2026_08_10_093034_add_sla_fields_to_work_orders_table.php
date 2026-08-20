<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('due_date')->constrained()->nullOnDelete();
            $table->dateTime('sla_response_due_at')->nullable()->after('sla_policy_id');
            $table->dateTime('sla_resolution_due_at')->nullable()->after('sla_response_due_at');
            $table->boolean('sla_breached')->default(false)->after('sla_resolution_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn(['sla_policy_id', 'sla_response_due_at', 'sla_resolution_due_at', 'sla_breached']);
        });
    }
};