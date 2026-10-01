<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterventionReportRequest;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InterventionReportController extends Controller
{
    /**
     * Crée ou met à jour le rapport d'intervention d'un OT.
     * (Un OT n'a qu'un seul rapport — updateOrCreate gère les deux cas)
     */
    public function store(StoreInterventionReportRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        // Le rapport porte le nom de son auteur : seule la personne assignée l'écrit.
        $this->authorize('perform', $workOrder);

        // Un rapport signé est un document engagé : on ne l'écrase plus. Une erreur
        // se signale par un commentaire ou une demande de correction (contrôle qualité).
        if ($workOrder->interventionReport?->is_signed) {
            return redirect()->route('work-orders.show', $workOrder)
                ->with('warning', 'Ce rapport est déjà signé : il ne peut plus être modifié. Ajoutez un commentaire si besoin.');
        }

        $data = [
            'technician_id' => Auth::id(),
            'work_performed' => $request->validated('work_performed'),
            'parts_used' => $request->validated('parts_used'),
            'recommendations' => $request->validated('recommendations'),
        ];

        $isSigned = $request->filled('signature');

        if ($isSigned) {
            $data['signature_path'] = $this->saveSignature($request->validated('signature'), $workOrder);
            $data['signed_by_name'] = $request->validated('signed_by_name');
            $data['signed_at'] = now();
        }

        $workOrder->interventionReport()->updateOrCreate(
            ['work_order_id' => $workOrder->id],
            $data
        );

        // Fermeture automatique : la signature du rapport marque la fin de
        // l'intervention côté technicien, l'OT passe donc en "résolu" et
        // attend désormais la validation qualité (Module G), s'il n'y est
        // pas déjà et n'est pas encore fermé/rejeté.
        if ($isSigned && ! in_array($workOrder->status, ['resolu', 'ferme'])) {
            $oldStatus = $workOrder->status;

            $workOrder->update(['status' => 'resolu']);

            $workOrder->statusHistories()->create([
                'changed_by' => Auth::id(),
                'old_status' => $oldStatus,
                'new_status' => 'resolu',
                'note' => 'Passage automatique suite à la signature du rapport d\'intervention.',
            ]);
        }

        $message = $isSigned
            ? 'Rapport d\'intervention signé avec succès. L\'OT est maintenant marqué comme résolu.'
            : 'Rapport d\'intervention enregistré avec succès.';

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', $message);
    }

    /**
     * Décode l'image base64 reçue du canvas de signature et l'enregistre sur le disque.
     */
    private function saveSignature(string $base64Signature, WorkOrder $workOrder): string
    {
        $image = explode(',', $base64Signature)[1] ?? $base64Signature;

        $fileName = 'signature-' . Str::uuid() . '.png';
        $path = 'work-orders/' . $workOrder->id . '/signatures/' . $fileName;

        Storage::disk('public')->put($path, base64_decode($image));

        return $path;
    }
}