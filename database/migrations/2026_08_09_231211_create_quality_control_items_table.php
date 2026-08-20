<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_control_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_control_id')->constrained('work_order_quality_controls')->cascadeOnDelete();

            $table->string('label');
            $table->boolean('is_compliant')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_control_items');
    }
};