<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Bilan du mois d'un service demandeur (gouvernante, responsable de réception) :
 * les signalements de son équipe, leurs délais de réparation, ce qui revient.
 */
class MonthlyReport
{
    /** Mois demandé (?mois=2026-09), jamais dans le futur ; le mois en cours par défaut. */
    public static function month(Request $request): Carbon
    {
        $month = rescue(fn () => Carbon::createFromFormat('Y-m', $request->string('mois')->toString())->startOfMonth(), null, false)
            ?? now()->startOfMonth();

        return $month->isFuture() ? now()->startOfMonth() : $month;
    }

    /**
     * Chiffres d'un mois pour les signalements que l'utilisateur peut voir (son équipe).
     *
     * @return array<string, mixed>
     */
    public static function stats(User $user, Carbon $month): array
    {
        $orders = WorkOrder::visibleTo($user)
            ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
            ->with('room', 'reporter', 'priority', 'type')->get();

        $repaired = $orders->whereIn('status', Housekeeping::GROUPS['done'])->whereNotNull('completed_at');
        $withDeadline = $repaired->whereNotNull('sla_resolution_due_at');
        $hours = $repaired->map(fn (WorkOrder $w) => $w->created_at->diffInMinutes($w->completed_at) / 60);

        $count = fn (Collection $c) => $c->sortDesc()->take(6);

        return [
            'total' => $orders->count(),
            'urgent' => $orders->filter(fn (WorkOrder $w) => $w->priority?->code === 'urgente')->count(),
            // Réclamations de clients transmises (type « Demande client »).
            'complaints' => $orders->filter(fn (WorkOrder $w) => $w->type?->code === 'demande_client')->count(),
            'repaired' => $repaired->count(),
            'open' => $orders->whereNotIn('status', Housekeeping::FINISHED)->count(),
            'cancelled' => $orders->where('status', 'annule')->count(),
            'avgHours' => $hours->isEmpty() ? null : round($hours->avg(), 1),
            'onTime' => $withDeadline->isEmpty() ? null
                : (int) round($withDeadline->filter(fn (WorkOrder $w) => $w->completed_at->lte($w->sla_resolution_due_at))->count() / $withDeadline->count() * 100),
            'byCategory' => $count($orders->countBy(fn (WorkOrder $w) => ($c = Housekeeping::category($w)) ? Housekeeping::categoryLabel($c) : 'Autre (formulaire détaillé)')),
            'byRoom' => $count($orders->whereNotNull('room_id')->countBy(fn (WorkOrder $w) => $w->room?->label ?? '—')),
            'byAgent' => $count($orders->countBy(fn (WorkOrder $w) => $w->reporter?->name ?? '—')),
        ];
    }
}
