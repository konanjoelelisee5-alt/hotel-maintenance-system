<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\CorrectionRequest;
use App\Models\InterventionSession;
use App\Models\MaintenancePlan;
use App\Models\Part;
use App\Models\PartRequest;
use App\Models\PartReservation;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use Illuminate\Support\Collection;

/**
 * Contenu des dashboards selon le rôle : indicateurs du haut (pulse), panneaux
 * latéraux A et B, planning du jour (timeline).
 */
class DashboardPanels
{
    /**
     * Fenêtres d'analyse proposées par le sélecteur de période (Santé du SLA).
     * Clé d'URL => [libellé court, nombre d'heures, libellé long].
     */
    public const PERIODS = [
        '24h' => ['24 h', 24, '24 dernières heures'],
        '7d' => ['7 j', 24 * 7, '7 derniers jours'],
        '30d' => ['30 j', 24 * 30, '30 derniers jours'],
    ];

    /**
     * @return array<int, array{label: string, value: mixed, sub: string, color: string, filter: ?string}> (color : clé de Swatch)
     */
    public static function pulse(User $user): array
    {
        $mine = fn () => WorkOrder::visibleTo($user);
        $urgent = fn ($q) => $q->where('code', 'urgente');

        return match ($user->role) {
            // Ce qui demande une décision maintenant ; chaque carte ouvre l'onglet qui la détaille.
            UserRole::Admin => [
                ['label' => 'Urgences ouvertes', 'value' => WorkQueue::filtered($user, 'urgent')->count(), 'sub' => 'priorité maximale', 'color' => 'red', 'filter' => 'urgent'],
                ['label' => 'Non affectés', 'value' => WorkQueue::filtered($user, 'unassigned')->count(), 'sub' => 'à confier à un technicien', 'color' => 'gold', 'filter' => 'unassigned'],
                ['label' => 'En retard SLA', 'value' => WorkQueue::filtered($user, 'late')->count(), 'sub' => 'délai dépassé', 'color' => 'red', 'filter' => 'late'],
                ['label' => 'À contrôler', 'value' => WorkQueue::filtered($user, 'to_review')->count(), 'sub' => 'résolus, contrôle qualité', 'color' => 'blue', 'filter' => 'to_review'],
                ['label' => 'En attente', 'value' => WorkQueue::filtered($user, 'waiting')->count(), 'sub' => 'pièce, accès chambre…', 'color' => 'amber', 'filter' => 'waiting'],
            ],
            // Mêmes indicateurs de décision que l'admin (chacun ouvre son onglet), plus
            // le stock, que le manager réapprovisionne : cette carte-là ouvre la page Pièces.
            UserRole::Manager => [
                ['label' => 'Urgences ouvertes', 'value' => WorkQueue::filtered($user, 'urgent')->count(), 'sub' => 'priorité maximale', 'color' => 'red', 'filter' => 'urgent'],
                ['label' => 'Non affectés', 'value' => WorkQueue::filtered($user, 'unassigned')->count(), 'sub' => 'à répartir maintenant', 'color' => 'gold', 'filter' => 'unassigned'],
                ['label' => 'En retard SLA', 'value' => WorkQueue::filtered($user, 'late')->count(), 'sub' => 'délai dépassé', 'color' => 'red', 'filter' => 'late'],
                ['label' => 'À contrôler', 'value' => WorkQueue::filtered($user, 'to_review')->count(), 'sub' => 'résolus, contrôle qualité', 'color' => 'blue', 'filter' => 'to_review'],
                ['label' => 'En attente', 'value' => WorkQueue::filtered($user, 'waiting')->count(), 'sub' => 'pièce, accès chambre…', 'color' => 'amber', 'filter' => 'waiting'],
                ['label' => 'Stock sous le seuil', 'value' => self::lowStockCount(), 'sub' => 'pièces à commander', 'color' => 'gold', 'filter' => null, 'url' => route('parts.index', ['tab' => 'low'])],
            ],
            UserRole::Technicien => [
                ['label' => 'Mes ordres du jour', 'value' => (clone $mine())->whereDate('scheduled_at', today())->count(), 'sub' => "aujourd'hui", 'color' => 'navy', 'filter' => 'mine'],
                ['label' => 'Urgents', 'value' => (clone $mine())->open()->whereHas('priority', $urgent)->count(), 'sub' => 'SLA à surveiller', 'color' => 'red', 'filter' => 'urgent'],
                ['label' => 'Temps saisi', 'value' => InterventionSession::where('technician_id', $user->id)->whereDate('started_at', today())->sum('duration_minutes').' min', 'sub' => "aujourd'hui", 'color' => 'amber', 'filter' => 'mine'],
                ['label' => 'Terminés aujourd\'hui', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->whereDate('completed_at', today())->count(), 'sub' => 'objectif du jour', 'color' => 'green', 'filter' => 'mine'],
                ['label' => 'Pièces à retirer', 'value' => PartReservation::whereIn('work_order_id', (clone $mine())->pluck('id'))->where('status', 'reservee')->count(), 'sub' => 'réservées', 'color' => 'gold', 'filter' => 'mine'],
            ],
            UserRole::Housekeeping => [
                $user->isDepartmentHead()
                    ? ['label' => "Signalements de l'équipe", 'value' => (clone $mine())->open()->count(), 'sub' => 'ouverts', 'color' => 'blue', 'filter' => 'all']
                    : ['label' => 'Mes signalements', 'value' => (clone $mine())->open()->count(), 'sub' => 'ouverts', 'color' => 'blue', 'filter' => 'mine'],
                ['label' => "En attente d'affectation", 'value' => (clone $mine())->whereNull('assigned_to')->open()->count(), 'sub' => 'non affectés', 'color' => 'gold', 'filter' => 'unassigned'],
                ['label' => 'Résolus cette semaine', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->where('completed_at', '>=', now()->subWeek())->count(), 'sub' => 'clôturés', 'color' => 'green', 'filter' => 'mine'],
                ['label' => 'Chambres suivies', 'value' => (clone $mine())->distinct('room_id')->count('room_id'), 'sub' => 'avec signalement', 'color' => 'navy', 'filter' => 'mine'],
            ],
            UserRole::Reception => [
                ['label' => 'Demandes en cours', 'value' => (clone $mine())->open()->count(), 'sub' => $user->isDepartmentHead() ? "signalées par l'équipe" : 'signalées par la réception', 'color' => 'blue', 'filter' => $user->isDepartmentHead() ? 'all' : 'mine'],
                ['label' => 'Urgentes', 'value' => (clone $mine())->open()->whereHas('priority', $urgent)->count(), 'sub' => 'priorité maximale', 'color' => 'red', 'filter' => 'urgent'],
                ['label' => 'En attente client', 'value' => (clone $mine())->whereNull('assigned_to')->open()->count(), 'sub' => 'passage annoncé', 'color' => 'gold', 'filter' => 'unassigned'],
                ['label' => 'Clôturées aujourd\'hui', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->whereDate('completed_at', today())->count(), 'sub' => 'réponse donnée', 'color' => 'green', 'filter' => 'mine'],
            ],
        };
    }

    /**
     * @return array{title: string, sub: string, items: Collection}
     */
    public static function sideA(User $user, string $period = '7d'): array
    {
        [, $hours, $periodLabel] = self::PERIODS[$period];
        $since = now()->subHours($hours);

        if ($user->isDepartmentHead()) {
            return [
                'title' => "Signalements de l'équipe",
                'sub' => 'OT ouverts par agent',
                'items' => User::where('role', $user->role)->where('is_active', true)
                    ->withCount(['reportedWorkOrders as open_count' => fn ($q) => $q->open()])
                    ->orderByDesc('open_count')->orderBy('name')->get()
                    ->map(fn (User $agent) => ['name' => $agent->name, 'meta' => $agent->open_count.' ouvert(s)', 'pct' => min(100, $agent->open_count * 20), 'color' => $agent->open_count >= 5 ? 'red' : ($agent->open_count >= 3 ? 'amber' : 'green')]),
            ];
        }

        return match ($user->role) {
            UserRole::Technicien => [
                'title' => 'Ma journée, heure par heure',
                'sub' => now()->locale('fr')->translatedFormat('l j F'),
                'items' => WorkOrder::visibleTo($user)->whereDate('scheduled_at', today())->with('priority')->orderBy('scheduled_at')->get()
                    ->map(fn (WorkOrder $w) => ['name' => ($w->scheduled_at?->format('H:i') ?? '—').' · '.$w->title, 'meta' => $w->estimated_duration_minutes.' min', 'pct' => $w->slaProgressPercent(), 'color' => $w->slaColorClass()]),
            ],
            UserRole::Housekeeping, UserRole::Reception => [
                'title' => $user->role === UserRole::Reception ? 'Chambres avec demande active' : 'État de mes chambres',
                'sub' => 'à annoncer au client',
                'items' => WorkOrder::visibleTo($user)->open()->with('room')->limit(5)->get()
                    ->map(fn (WorkOrder $w) => ['name' => $w->room?->label ?? '—', 'meta' => $w->status_label, 'pct' => $w->slaProgressPercent(), 'color' => $w->slaColorClass()]),
            ],
            UserRole::Admin => [
                'title' => 'Santé du SLA',
                'sub' => $periodLabel,
                'items' => WorkOrderPriority::orderBy('position')->get()->map(function (WorkOrderPriority $p) use ($since) {
                    $total = WorkOrder::where('priority_id', $p->id)->where('created_at', '>=', $since)->count();
                    $breached = WorkOrder::where('priority_id', $p->id)->where('created_at', '>=', $since)->where('sla_breached', true)->count();
                    $rate = $total > 0 ? (int) round((1 - $breached / $total) * 100) : 100;

                    return ['name' => 'Priorité '.mb_strtolower($p->label), 'meta' => $rate.' %', 'pct' => $rate, 'color' => $rate >= 90 ? 'green' : ($rate >= 75 ? 'amber' : 'red'), 'dot' => $p->color];
                }),
            ],
            default => [
                'title' => 'Charge des techniciens',
                'sub' => now()->locale('fr')->translatedFormat('l').' · '.User::where('role', UserRole::Technicien)->where('is_active', true)->count().' techniciens',
                'items' => User::where('role', UserRole::Technicien)->where('is_active', true)
                    ->withCount(['assignedWorkOrders as open_count' => fn ($q) => $q->open()])
                    ->orderByDesc('open_count')->get()
                    ->map(fn (User $t) => ['name' => $t->name, 'meta' => $t->open_count.' ordre(s)', 'pct' => min(100, $t->open_count * 20), 'color' => $t->open_count >= 5 ? 'red' : ($t->open_count >= 3 ? 'amber' : 'green')]),
            ],
        };
    }

    /**
     * @return array{title: string, items: Collection}
     */
    public static function sideB(User $user): array
    {
        return match ($user->role) {
            // Avant de quitter l'atelier : les corrections à reprendre, les clients à
            // ménager (présent, retour ou arrivée attendus) et les pièces à prendre au magasin.
            UserRole::Technicien => [
                'title' => 'À savoir avant de partir',
                'items' => CorrectionRequest::whereHas('workOrder', fn ($q) => $q->where('assigned_to', $user->id))->where('status', 'ouverte')->with('workOrder')->get()
                    ->map(fn (CorrectionRequest $c) => ['label' => 'Correction demandée sur '.$c->workOrder->code(), 'meta' => 'Contrôle qualité refusé', 'color' => 'red'])
                    ->concat(
                        WorkOrder::visibleTo($user)->open()->whereNotNull('room_id')->with('room', 'comments.user')->get()
                            ->map(fn (WorkOrder $w) => [$w, $w->guestNotice()])
                            ->filter(fn (array $pair) => $pair[1] && $pair[1]['tone'] !== 'green')
                            ->map(fn (array $pair) => ['label' => $pair[0]->room->label.' : '.\Illuminate\Support\Str::lcfirst($pair[1]['title']), 'meta' => $pair[0]->code().' · '.$pair[1]['detail'], 'color' => $pair[1]['tone']])
                    )
                    ->concat(
                        PartReservation::whereIn('work_order_id', WorkOrder::visibleTo($user)->open()->select('id'))->where('status', 'reservee')->with('part', 'workOrder')->get()
                            ->map(fn (PartReservation $r) => ['label' => 'Prendre au magasin : '.$r->quantity.' '.$r->part->unit.' '.$r->part->name, 'meta' => $r->workOrder->code().' · réservée', 'color' => 'gold'])
                    )->values(),
            ],
            UserRole::Housekeeping, UserRole::Reception => [
                'title' => match (true) {
                    $user->isDepartmentHead() => "Derniers signalements de l'équipe",
                    $user->role === UserRole::Reception => 'Réponses à donner au client',
                    default => 'Suivi de mes signalements',
                },
                'items' => WorkOrder::visibleTo($user)->with('room', 'assignee', 'reporter')->latest()->limit(4)->get()
                    ->map(fn (WorkOrder $w) => [
                        'label' => ($w->room?->label ?? '—').' — '.$w->title,
                        // Le responsable a besoin de savoir quel agent a signalé.
                        'meta' => $w->code().' · '.($user->isDepartmentHead() ? ($w->reporter?->name ?? '—').' · ' : '').($w->assignee?->name ?? $w->status_label),
                        'color' => $w->slaColorClass(),
                        // Activité récente (écran HK) : heure et fiche liée.
                        'time' => $w->created_at->format('H:i'),
                        'id' => $w->id,
                    ]),
            ],
            UserRole::Admin => [
                // Les connexions noieraient le reste : elles ont leur filtre dans le journal.
                'title' => 'Activité administrative',
                'items' => ActivityLog::with('user')->where('action', 'not like', 'auth.%')->latest('created_at')->limit(4)->get()
                    ->map(fn (ActivityLog $a) => ['label' => $a->description, 'meta' => $a->action.' · '.$a->created_at->format('H:i'), 'color' => 'blue', 'time' => $a->created_at->format('H:i'), 'who' => $a->user?->name]),
            ],
            default => [
                // Achats en retard + stock sous le seuil + maintenance préventive à venir.
                // (« Blocages » se confondait avec les chambres bloquées.)
                'title' => 'Achats, stock et préventif à suivre',
                // Pièces absentes du magasin qu'un technicien attend : en tête.
                'items' => PartRequest::pending()->with('workOrder', 'requester')->oldest()->take(3)->get()
                    ->map(fn (PartRequest $r) => ['label' => 'Pièce demandée : '.$r->quantity.' × '.$r->description, 'meta' => $r->workOrder->code().' · '.($r->requester?->name ?? '—').' · '.$r->created_at->format('d/m H:i'), 'color' => 'amber'])
                    ->concat(PurchaseOrder::whereIn('status', ['brouillon', 'envoyee', 'confirmee'])->where('expected_delivery_date', '<', now())->with('supplier')->limit(2)->get()
                    ->map(fn (PurchaseOrder $po) => ['label' => $po->number.' : livraison en retard', 'meta' => $po->supplier->name.' · prévue le '.$po->expected_delivery_date->format('d/m'), 'color' => 'red']))
                    ->concat(
                        Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->take(2)->get()
                            ->map(fn (Part $p) => ['label' => 'Stock « '.$p->name.' » sous le seuil', 'meta' => $p->quantity_on_hand.' restant(s)', 'color' => 'gold'])
                    )
                    ->concat(
                        MaintenancePlan::active()->where('next_due_at', '<=', now()->addDays(7))->with('equipment', 'room')->take(2)->get()
                            ->map(fn (MaintenancePlan $p) => ['label' => 'Maintenance « '.$p->name.' » à générer', 'meta' => ($p->equipment?->name ?? $p->room?->label ?? 'préventif').' · '.$p->next_due_at->format('d/m'), 'color' => 'blue'])
                    )->values(),
            ],
        };
    }

    public static function timeline(User $user): Collection
    {
        return WorkOrder::visibleTo($user)->whereDate('scheduled_at', today())->with('assignee')->orderBy('scheduled_at')->limit(6)->get()
            ->map(fn (WorkOrder $w) => ['time' => $w->scheduled_at?->format('H:i') ?? '—', 'title' => $w->title, 'who' => $w->assignee?->name ?? 'Non affecté', 'id' => $w->id]);
    }

    private static function lowStockCount(): int
    {
        return Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->count();
    }
}
