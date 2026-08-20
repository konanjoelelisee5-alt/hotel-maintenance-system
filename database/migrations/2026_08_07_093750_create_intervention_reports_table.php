<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            $table->text('work_performed');
            $table->text('parts_used')->nullable();
            $table->text('recommendations')->nullable();

            $table->string('signature_path')->nullable();
            $table->string('signed_by_name')->nullable();
            $table->dateTime('signed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_reports');
    }
};