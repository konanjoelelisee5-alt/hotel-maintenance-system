<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La table "rooms" devient celle des LIEUX de l'hôtel : les chambres, mais
        // aussi les espaces communs (chaufferie, cuisine, piscine, hall...). Les OT,
        // équipements et plans préventifs, qui pointent déjà vers room_id, peuvent
        // ainsi viser un espace commun sans autre changement.
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('type', ['chambre', 'espace_commun'])->default('chambre')->after('id');
            // Nom affiché des espaces communs ("Piscine") ; vide pour une chambre,
            // désignée par son numéro. "number" sert de code court unique (ex. PISC).
            $table->string('name')->nullable()->after('number');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['type', 'name']);
        });
    }
};
