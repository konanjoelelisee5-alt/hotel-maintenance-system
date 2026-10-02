<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InterventionSession;
use App\Models\WorkOrder;
use App\Support\Duration;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InterventionSessionController extends Controller
{
    /**
     * Démarre une nouvelle session de travail sur un OT.
     */
    public function start(WorkOrder $workOrder): RedirectResponse
    {
        // Seule la personne assignée chronomètre : le temps est enregistré à son nom.
        $this->authorize('perform', $workOrder);
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
        $this->authorize('perform', $workOrder);
        $session = $workOrder->activeSession();

        if (! $session) {
            return back()->with('warning', 'Aucune session de travail en cours à arrêter.');
        }

        $session->stop();

        return back()->with('success', 'Intervention mise en pause. Durée enregistrée : ' . $session->duration_minutes . ' minutes.');
    }

    /**
     * Saisit après coup un temps de travail dont le chrono n'a pas été lancé.
     */
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('perform', $workOrder);
        [$start, $end] = $this->validatedPeriod($request);

        $session = $workOrder->interventionSessions()->create([
            'technician_id' => Auth::id(),
            'started_at' => $start,
            'ended_at' => $end,
            'duration_minutes' => InterventionSession::minutesBetween($start, $end),
            'is_manual' => true,
        ]);

        ActivityLog::record('intervention_session.added', "Temps saisi à la main sur l'OT {$workOrder->code()} : {$session->duration_minutes} min ({$start->format('d/m H\hi')} → {$end->format('H\hi')})", $workOrder);

        return back()->with('success', 'Temps ajouté : '.Duration::human($session->duration_minutes).'.');
    }

    /**
     * Corrige une de ses sessions : horaires mal saisis, ou chrono resté ouvert.
     */
    public function update(Request $request, WorkOrder $workOrder, InterventionSession $interventionSession): RedirectResponse
    {
        $session = $interventionSession;
        $this->authorize('perform', $workOrder);
        abort_unless($session->technician_id === Auth::id(), 403);
        [$start, $end] = $this->validatedPeriod($request, $session);

        $before = $session->started_at->format('d/m H\hi').' → '.($session->ended_at?->format('H\hi') ?? 'en cours');
        $session->update([
            'started_at' => $start,
            'ended_at' => $end,
            'duration_minutes' => InterventionSession::minutesBetween($start, $end),
            'corrected_at' => now(),
        ]);

        ActivityLog::record('intervention_session.corrected', "Temps corrigé sur l'OT {$workOrder->code()} : {$before} devient {$start->format('d/m H\hi')} → {$end->format('H\hi')}", $workOrder);

        return back()->with('success', 'Temps corrigé : '.Duration::human($session->duration_minutes).'.');
    }

    /**
     * Début et fin d'une période de travail : dans le passé, fin après début, au plus
     * 12 h, sans chevaucher une autre de ses sessions sur cet OT.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function validatedPeriod(Request $request, ?InterventionSession $ignore = null): array
    {
        $data = $request->validate([
            'started_at' => ['required', 'date', 'before_or_equal:now'],
            'ended_at' => ['required', 'date', 'after:started_at', 'before_or_equal:now'],
        ], [
            'ended_at.after' => 'La fin doit être après le début.',
            'started_at.before_or_equal' => 'Le début ne peut pas être dans le futur.',
            'ended_at.before_or_equal' => 'La fin ne peut pas être dans le futur.',
        ]);

        $start = Carbon::parse($data['started_at']);
        $end = Carbon::parse($data['ended_at']);

        if (InterventionSession::minutesBetween($start, $end) > InterventionSession::MAX_MANUAL_MINUTES) {
            throw ValidationException::withMessages(['ended_at' => 'Une période ne peut pas dépasser 12 heures : saisissez-la en plusieurs fois.']);
        }

        $overlaps = InterventionSession::where('technician_id', Auth::id())
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->where('started_at', '<', $end)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $start))
            ->exists();
        if ($overlaps) {
            throw ValidationException::withMessages(['started_at' => 'Cette période chevauche un autre temps déjà enregistré.']);
        }

        return [$start, $end];
    }
}