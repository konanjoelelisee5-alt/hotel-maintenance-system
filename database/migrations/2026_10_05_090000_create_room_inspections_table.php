<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tournée d'inspection des chambres (gouvernante) : une inspection par passage,
 * un point par élément vérifié. Un point non conforme crée (ou complète) un OT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['en_cours', 'terminee'])->default('en_cours');
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'completed_at']);
        });

        Schema::create('room_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_inspection_id')->constrained()->cascadeOnDelete();
            // Point de la liste (App\Support\RoomInspectionChecklist), recopié : la liste peut évoluer.
            $table->string('point_key');
            $table->string('zone');
            $table->string('label');
            $table->string('category');
            $table->enum('result', ['ok', 'nok', 'na'])->nullable();
            $table->text('comment')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_inspection_items');
        Schema::dropIfExists('room_inspections');
    }
};
