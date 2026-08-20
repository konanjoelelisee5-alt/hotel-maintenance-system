<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_control_id')->nullable()->constrained('work_order_quality_controls')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            $table->text('description');
            $table->enum('status', ['ouverte', 'traitee'])->default('ouverte');
            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};