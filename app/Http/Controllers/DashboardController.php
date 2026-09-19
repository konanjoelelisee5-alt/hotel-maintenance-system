<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\CorrectionRequest;
use App\Models\InterventionSession;
use App\Models\MaintenancePlan;
use App\Models\Part;
use App\Models\PartReservation;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderQualityControl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(Request $request): View
    {
        return $this->render('dashboards.admin', $request);
    }

    public function manager(Request $request): View
    {
        return $this->render('dashboards.manager', $request);
    }

    public function technicien(Request $request): View
    {
        return $this->render('dashboards.technicien', $request);
    }

    public function housekeeping(Request $request): View
    {
        return $this->render('dashboards.housekeeping', $request);
    }

    public function reception(Request $request): View
    {
        return $this->render('dashboards.reception', $request);
    }

    /**
     * Construit les données communes "pulse / file d'attente / panneaux latéraux /
     * timeline" pour la vue de dashboard du rôle courant.
     */
    private function render(string $view, Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $filter = $request->string('filter')->toString() ?: 'urgent';

        return view($view, [
            'pulse' => $this->pulse($user),
            'queue' => $this->queue($user, $filter),
            'filter' => $filter,
            'filters' => $this->filterOptions($user),
            'sideA' => $this->sideA($user),
            'sideB' => $this->sideB($user),
            'timeline' => $this->timeline($user),
        ]);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function filterOptions(User $user): array
    {
        if ($user->role->seesAllWorkOrders()) {
            return [
                ['key' => 'urgent', 'label' => 'Urgents'],
                ['key' => 'unassigned', 'label' => 'Non affectés'],
                ['key' => 'late', 'label' => 'En retard SLA'],
                ['key' => 'all', 'label' => 'Tous'],
            ];
        }

        return [
            ['key' => 'mine', 'label' => $user->role === UserRole::Technicien ? 'Mes ordres' : 'Mes signalements'],
            ['key' => 'urgent', 'label' => 'Urgents'],
            ['key' => 'all', 'label' => 'Tous'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: mixed, sub: string, filter: string}>
     */
    private function pulse(User $user): array
    {
        $mine = fn () => WorkOrder::visibleTo($user);
        $urgent = fn ($q) => $q->where('code', 'urgente');

        return match ($user->role) {
            UserRole::Admin => [
                ['label' => 'Ordres au total', 'value' => WorkOrder::count(), 'sub' => WorkOrder::open()->count().' ouverts', 'filter' => 'all'],
                ['label' => 'SLA dépassés', 'value' => WorkOrder::where('sla_breached', true)->count(), 'sub' => 'à traiter en priorité', 'filter' => 'late'],
                ['label' => 'Taux de respect SLA', 'value' => $this->slaComplianceRate().' %', 'sub' => 'global', 'filter' => 'all'],
                ['label' => 'Problèmes qualité', 'value' => WorkOrderQualityControl::where('status', 'rejete')->count(), 'sub' => 'refusés', 'filter' => 'all'],
                ['label' => 'Comptes actifs', 'value' => User::where('is_active', true)->count(), 'sub' => 'sur '.User::count(), 'filter' => 'all'],
            ],
            UserRole::Manager => [
                ['label' => 'Ordres urgents', 'value' => (clone $mine())->whereHas('priority', $urgent)->count(), 'sub' => 'priorité maximale', 'filter' => 'urgent'],
                ['label' => 'Non affectés', 'value' => (clone $mine())->whereNull('assigned_to')->open()->count(), 'sub' => 'à répartir maintenant', 'filter' => 'unassigned'],
                ['label' => 'En cours', 'value' => (clone $mine())->where('status', 'en_cours')->count(), 'sub' => 'techniciens en poste', 'filter' => 'all'],
                ['label' => 'Contrôle qualité', 'value' => WorkOrderQualityControl::where('status', 'en_attente')->count(), 'sub' => 'à vérifier', 'filter' => 'all'],
                ['label' => 'Blocages stock', 'value' => $this->lowStockCount(), 'sub' => 'sous le seuil', 'filter' => 'all'],
            ],
            UserRole::Technicien => [
                ['label' => 'Mes ordres du jour', 'value' => (clone $mine())->whereDate('scheduled_at', today())->count(), 'sub' => "aujourd'hui", 'filter' => 'mine'],
                ['label' => 'Urgents', 'value' => (clone $mine())->open()->whereHas('priority', $urgent)->count(), 'sub' => 'SLA à surveiller', 'filter' => 'urgent'],
                ['label' => 'Temps saisi', 'value' => InterventionSession::where('technician_id', $user->id)->whereDate('started_at', today())->sum('duration_minutes').' min', 'sub' => "aujourd'hui", 'filter' => 'mine'],
                ['label' => 'Terminés aujourd\'hui', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->whereDate('completed_at', today())->count(), 'sub' => 'objectif du jour', 'filter' => 'mine'],
                ['label' => 'Pièces à retirer', 'value' => PartReservation::whereIn('work_order_id', (clone $mine())->pluck('id'))->where('status', 'reservee')->count(), 'sub' => 'réservées', 'filter' => 'mine'],
            ],
            UserRole::Housekeeping => [
                ['label' => 'Mes signalements', 'value' => (clone $mine())->open()->count(), 'sub' => 'ouverts', 'filter' => 'mine'],
                ['label' => "En attente d'affectation", 'value' => (clone $mine())->whereNull('assigned_to')->open()->count(), 'sub' => 'non affectés', 'filter' => 'unassigned'],
                ['label' => 'Résolus cette semaine', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->where('updated_at', '>=', now()->subWeek())->count(), 'sub' => 'clôturés', 'filter' => 'mine'],
                ['label' => 'Chambres suivies', 'value' => (clone $mine())->distinct('room_id')->count('room_id'), 'sub' => 'avec signalement', 'filter' => 'mine'],
            ],
            UserRole::Reception => [
                ['label' => 'Demandes en cours', 'value' => (clone $mine())->open()->count(), 'sub' => 'signalées par la réception', 'filter' => 'mine'],
                ['label' => 'Urgentes', 'value' => (clone $mine())->open()->whereHas('priority', $urgent)->count(), 'sub' => 'priorité maximale', 'filter' => 'urgent'],
                ['label' => 'En attente client', 'value' => (clone $mine())->whereNull('assigned_to')->open()->count(), 'sub' => 'passage annoncé', 'filter' => 'unassigned'],
                ['label' => 'Clôturées aujourd\'hui', 'value' => (clone $mine())->whereIn('status', ['resolu', 'ferme'])->whereDate('completed_at', today())->count(), 'sub' => 'réponse donnée', 'filter' => 'mine'],
            ],
        };
    }

    private function queue(User $user, string $filter): Collection
    {
        $query = WorkOrder::visibleTo($user)->with(['room', 'equipment', 'assignee', 'priority']);

        match ($filter) {
            'urgent' => $query->whereHas('priority', fn ($p) => $p->where('code', 'urgente')),
            'unassigned' => $query->whereNull('assigned_to'),
            'late' => $query->where('sla_breached', true),
            default => null,
        };

        return $query->orderByRaw('CASE WHEN sla_breached THEN 0 ELSE 1 END')
            ->orderBy('sla_resolution_due_at')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{title: string, sub: string, items: Collection}
     */
    private function sideA(User $user): array
    {
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
                    ->map(fn (WorkOrder $w) => ['name' => 'Chambre '.($w->room?->number ?? '—'), 'meta' => $w->status_label, 'pct' => $w->slaProgressPercent(), 'color' => $w->slaColorClass()]),
            ],
            UserRole::Admin => [
                'title' => 'Santé du SLA',
                'sub' => '7 derniers jours',
                'items' => WorkOrderPriority::orderBy('position')->get()->map(function (WorkOrderPriority $p) {
                    $total = WorkOrder::where('priority_id', $p->id)->where('created_at', '>=', now()->subDays(7))->count();
                    $breached = WorkOrder::where('priority_id', $p->id)->where('created_at', '>=', now()->subDays(7))->where('sla_breached', true)->count();
                    $rate = $total > 0 ? (int) round((1 - $breached / $total) * 100) : 100;

                    return ['name' => 'Priorité '.mb_strtolower($p->label), 'meta' => $rate.' %', 'pct' => $rate, 'color' => $rate >= 90 ? 'green' : ($rate >= 75 ? 'amber' : 'red')];
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
    private function sideB(User $user): array
    {
        return match ($user->role) {
            UserRole::Technicien => [
                'title' => 'À savoir avant de partir',
                'items' => collect([Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->first()])
                    ->filter()
                    ->map(fn (Part $p) => ['label' => $p->name.' disponible au magasin ('.$p->quantity_on_hand.')', 'meta' => 'Pièce · à réserver', 'color' => 'green'])
                    ->concat(
                        CorrectionRequest::whereHas('workOrder', fn ($q) => $q->where('assigned_to', $user->id))->where('status', 'ouverte')->with('workOrder')->get()
                            ->map(fn (CorrectionRequest $c) => ['label' => 'Correction demandée sur '.$c->workOrder->code(), 'meta' => 'Contrôle qualité refusé', 'color' => 'red'])
                    )->values(),
            ],
            UserRole::Housekeeping, UserRole::Reception => [
                'title' => $user->role === UserRole::Reception ? 'Réponses à donner au client' : 'Suivi de mes signalements',
                'items' => WorkOrder::visibleTo($user)->with('room', 'assignee')->latest()->limit(4)->get()
                    ->map(fn (WorkOrder $w) => ['label' => 'Chambre '.($w->room?->number ?? '—').' — '.$w->title, 'meta' => $w->code().' · '.($w->assignee?->name ?? $w->status_label), 'color' => $w->slaColorClass()]),
            ],
            UserRole::Admin => [
                'title' => 'Activité récente',
                'items' => ActivityLog::with('user')->latest('created_at')->limit(4)->get()
                    ->map(fn (ActivityLog $a) => ['label' => $a->description, 'meta' => $a->action.' · '.$a->created_at->format('H:i'), 'color' => 'blue']),
            ],
            default => [
                'title' => 'Blocages à décider',
                // Achats en retard + stock sous le seuil + maintenance préventive à venir :
                // modules propres à notre app (absents de la maquette de référence),
                // conservés ici tels quels (cf. plan de refonte, Phase 1).
                'items' => PurchaseOrder::whereIn('status', ['brouillon', 'envoyee', 'confirmee'])->where('expected_delivery_date', '<', now())->with('supplier')->limit(2)->get()
                    ->map(fn (PurchaseOrder $po) => ['label' => $po->number.' non réceptionné', 'meta' => $po->supplier->name, 'color' => 'red'])
                    ->concat(
                        Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->take(2)->get()
                            ->map(fn (Part $p) => ['label' => 'Stock « '.$p->name.' » sous le seuil', 'meta' => $p->quantity_on_hand.' restant(s)', 'color' => 'gold'])
                    )
                    ->concat(
                        MaintenancePlan::active()->where('next_due_at', '<=', now()->addDays(7))->with('equipment', 'room')->take(2)->get()
                            ->map(fn (MaintenancePlan $p) => ['label' => 'Maintenance « '.$p->name.' » à générer', 'meta' => ($p->equipment?->name ?? $p->room?->number ?? 'préventif').' · '.$p->next_due_at->format('d/m'), 'color' => 'blue'])
                    )->values(),
            ],
        };
    }

    private function timeline(User $user): Collection
    {
        return WorkOrder::visibleTo($user)->whereDate('scheduled_at', today())->with('assignee')->orderBy('scheduled_at')->limit(6)->get()
            ->map(fn (WorkOrder $w) => ['time' => $w->scheduled_at?->format('H:i') ?? '—', 'title' => $w->title, 'who' => $w->assignee?->name ?? 'Non affecté', 'id' => $w->id]);
    }

    private function slaComplianceRate(): int
    {
        $total = WorkOrder::count();
        $breached = WorkOrder::where('sla_breached', true)->count();

        return $total > 0 ? (int) round((1 - $breached / $total) * 100) : 100;
    }

    private function lowStockCount(): int
    {
        return Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->count();
    }
}
