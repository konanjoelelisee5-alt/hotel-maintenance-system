<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['priority_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['type', 'priority']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('type_id')->references('id')->on('work_order_types')->restrictOnDelete();
            $table->foreign('priority_id')->references('id')->on('work_order_priorities')->restrictOnDelete();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable(false)->change();
            $table->foreignId('priority_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->change();
            $table->foreignId('priority_id')->nullable()->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->enum('type', ['maintenance', 'demande_client', 'preventif'])->default('maintenance')->after('equipment_id');
            $table->enum('priority', ['basse', 'moyenne', 'haute', 'urgente'])->default('moyenne')->after('status');
        });
    }
};