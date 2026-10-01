<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Photos, messages vocaux, signatures et factures quittent le disque public
 * (lisible par lien sans connexion) pour le disque privé, servi par des routes
 * qui vérifient les droits. Les chemins en base ne changent pas : seul le disque change.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->moveAll(from: 'public', to: 'local');
    }

    public function down(): void
    {
        $this->moveAll(from: 'local', to: 'public');
    }

    private function moveAll(string $from, string $to): void
    {
        $paths = DB::table('work_order_attachments')->pluck('file_path')
            ->concat(DB::table('invoices')->whereNotNull('file_path')->pluck('file_path'))
            ->concat(DB::table('intervention_reports')->whereNotNull('signature_path')->pluck('signature_path'));

        foreach ($paths->filter()->unique() as $path) {
            // Rejouable : un fichier déjà déplacé (ou disparu, ex. disque éphémère de la démo) est ignoré.
            if (! Storage::disk($from)->exists($path) || Storage::disk($to)->exists($path)) {
                continue;
            }

            Storage::disk($to)->writeStream($path, Storage::disk($from)->readStream($path));
            Storage::disk($from)->delete($path);
        }
    }
};
