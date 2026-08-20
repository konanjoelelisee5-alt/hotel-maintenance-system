<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('color')->default('#6b7280');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('work_order_priorities')->insert([
            ['code' => 'basse', 'label' => 'Basse', 'color' => '#6b7280', 'position' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'moyenne', 'label' => 'Moyenne', 'color' => '#2563eb', 'position' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'haute', 'label' => 'Haute', 'color' => '#ea580c', 'position' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'urgente', 'label' => 'Urgente', 'color' => '#dc2626', 'position' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_priorities');
    }
};