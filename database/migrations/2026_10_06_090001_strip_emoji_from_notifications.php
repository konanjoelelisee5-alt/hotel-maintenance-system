<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les messages des notifications ne commencent plus par un emoji (⚠️, ✅, 🔧…) :
 * on nettoie aussi ceux déjà enregistrés, pour un affichage homogène.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $data = json_decode($row->data, true);
                $message = $data['message'] ?? null;
                if (! is_string($message)) {
                    continue;
                }

                $clean = preg_replace('/^[\x{2190}-\x{21FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}\x{FE0F}\s]+/u', '', $message);
                if ($clean !== $message) {
                    $data['message'] = $clean;
                    DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
                }
            }
        });
    }

    public function down(): void
    {
        // Rien à restaurer : le texte sans emoji reste juste.
    }
};
