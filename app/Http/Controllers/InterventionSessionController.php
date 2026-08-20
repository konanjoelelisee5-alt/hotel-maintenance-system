<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InterventionSessionController extends Controller
{
    /**
     * Démarre une nouvelle session de travail sur un OT.
     */
    public function start(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        // Sécurité : on empêche de démarrer une deuxième session si une est déjà active
        if ($workOrder->activeSession()) {
            return back()->with('warning', 'Une session de travail est déjà en cours sur cet OT.');
        }

        $workOrder->interventionSessions()->create([
            'technician_id' => Auth::id(),
            'started_at' => now(),
        ]);

        // Si c'est la toute première session, on passe automatiquement l'OT en "en_cours"
        if ($workOrder->status === 'ouvert') {
            $workOrder->update(['status' => 'en_cours']);

            $workOrder->statusHistories()->create([
                'changed_by' => Auth::id(),
                'old_status' => 'ouvert',
                'new_status' => 'en_cours',
                'note' => 'Passage automatique au démarrage de l\'intervention.',
            ]);
        }

        return back()->with('success', 'Intervention démarrée.');
    }

    /**
     * Arrête la session de travail actuellement active sur un OT.
     */
    public function stop(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        $session = $workOrder->activeSession();

        if (! $session) {
            return back()->with('warning', 'Aucune session de travail en cours à arrêter.');
        }

        $session->stop();

        return back()->with('success', 'Intervention mise en pause. Durée enregistrée : ' . $session->duration_minutes . ' minutes.');
    }
}