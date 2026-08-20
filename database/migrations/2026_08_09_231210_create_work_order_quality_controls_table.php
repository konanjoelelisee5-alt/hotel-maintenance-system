<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->text('overall_comment')->nullable();
            $table->dateTime('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_quality_controls');
    }
};