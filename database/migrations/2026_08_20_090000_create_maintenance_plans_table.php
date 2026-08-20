<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Cible du plan : au moins une chambre ou un équipement (validé côté application)
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            // Paramétrage de l'OT généré automatiquement
            $table->foreignId('work_order_type_id')->constrained('work_order_types')->restrictOnDelete();
            $table->foreignId('work_order_priority_id')->constrained('work_order_priorities')->restrictOnDelete();
            $table->foreignId('checklist_template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();

            // Auto-assignation : technicien fixe, ou compétence requise (le plus disponible/le moins chargé est choisi)
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();

            // Récurrence
            $table->enum('frequency_unit', ['jour', 'semaine', 'mois', 'trimestre', 'annee'])->default('mois');
            $table->unsignedInteger('frequency_interval')->default(1);
            $table->unsignedInteger('lead_time_days')->default(0);

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_at')->nullable();
            $table->dateTime('last_generated_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_plans');
    }
};
