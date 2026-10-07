<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Technicien : « J'ai vu, je m'en occupe » (prise en charge d'un OT affecté) et
 * demande d'une pièce absente du magasin, traitée par le manager.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Remis à vide à chaque changement de technicien (WorkOrder::booted).
            $table->dateTime('acknowledged_at')->nullable()->after('assigned_to');
        });

        // OT déjà affectés avant cette version : considérés comme vus (sinon tous
        // afficheraient « Pas encore vu » le jour de la mise en service).
        DB::table('work_orders')->whereNotNull('assigned_to')
            ->update(['acknowledged_at' => DB::raw('COALESCE(started_at, updated_at, created_at)')]);

        Schema::create('part_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            // Décrite en mots : la pièce n'existe pas (encore) dans le catalogue.
            $table->string('description', 200);
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('status', ['demandee', 'traitee'])->default('demandee');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('handled_at')->nullable();
            $table->string('handling_note', 300)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_requests');

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('acknowledged_at');
        });
    }
};
