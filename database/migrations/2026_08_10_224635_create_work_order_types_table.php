<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // On insère immédiatement les valeurs qui existaient jusqu'ici en dur (enum),
        // pour que la table soit prête à l'emploi dès sa création.
        DB::table('work_order_types')->insert([
            ['code' => 'maintenance', 'label' => 'Maintenance', 'position' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'demande_client', 'label' => 'Demande client', 'position' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'preventif', 'label' => 'Préventif', 'position' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_types');
    }
};