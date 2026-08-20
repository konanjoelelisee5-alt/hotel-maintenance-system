<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pour chaque type/priorité du référentiel, on met à jour tous les OT
        // dont l'ancienne colonne enum correspond, en leur assignant le bon ID.
        $types = DB::table('work_order_types')->get();
        foreach ($types as $type) {
            DB::table('work_orders')->where('type', $type->code)->update(['type_id' => $type->id]);
        }

        $priorities = DB::table('work_order_priorities')->get();
        foreach ($priorities as $priority) {
            DB::table('work_orders')->where('priority', $priority->code)->update(['priority_id' => $priority->id]);
        }
    }

    public function down(): void
    {
        // On ne remet pas les valeurs à null : si on annule cette migration,
        // les anciennes colonnes enum existent toujours et restent la source de vérité.
    }
};