<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('type')->constrained('work_order_types')->restrictOnDelete();
            $table->foreignId('priority_id')->nullable()->after('priority')->constrained('work_order_priorities')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['priority_id']);
            $table->dropColumn(['type_id', 'priority_id']);
        });
    }
};