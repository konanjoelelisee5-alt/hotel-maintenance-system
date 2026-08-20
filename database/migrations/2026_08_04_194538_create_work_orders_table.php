<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();

            $table->enum('type', ['maintenance', 'demande_client', 'preventif'])->default('maintenance');
            $table->enum('status', ['ouvert', 'en_cours', 'en_attente', 'resolu', 'ferme'])->default('ouvert');
            $table->enum('priority', ['basse', 'moyenne', 'haute', 'urgente'])->default('moyenne');

            $table->dateTime('due_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};