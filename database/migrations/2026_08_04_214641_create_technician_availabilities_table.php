<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Type : disponibilité récurrente hebdomadaire OU indisponibilité ponctuelle (congé)
            $table->enum('type', ['disponible', 'conge', 'absence'])->default('disponible');

            // Pour une disponibilité récurrente : jour de la semaine (0 = dimanche ... 6 = samedi)
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // Pour une indisponibilité ponctuelle (congé/absence) : dates précises
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();

            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_availabilities');
    }
};