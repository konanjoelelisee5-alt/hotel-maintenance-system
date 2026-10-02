<?php

namespace App\Http\Controllers;

use App\Actions\ReportWorkOrder;
use App\Enums\UserRole;
use App\Http\Requests\StoreWorkOrderAttachmentRequest;
use App\Http\Requests\StoreWorkOrderCommentRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderStatusRequest;
use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        $query = WorkOrder::visibleTo($authUser)->with(['room', 'equipment', 'assignee', 'reporter', 'type', 'priority']);

        $q = $request->string('q')->toString();
        $filter = $request->string('filter')->toString() ?: ($authUser->role->seesAllWorkOrders() ? 'all' : 'mine');
        $sort = $request->string('sort')->toString() ?: 'due';

        // Un onglet = un filtre ; les compteurs des onglets et des indicateurs
        // utilisent exactement la même règle que la liste.
        $applyFilter = fn ($qr, string $key) => match ($key) {
            'mine' => $qr->when($authUser->isDepartmentHead(), fn ($m) => $m->where('reported_by', $authUser->id)),
            'open' => $qr->open(),
            // Mêmes onglets et mêmes règles que la file de la Supervision : un OT réparé,
            // fermé ou annulé ne figure plus parmi les urgents ou les retards.
            'urgent' => $qr->open()->whereHas('priority', fn ($p) => $p->where('code', 'urgente')),
            'unassigned' => $qr->whereNull('assigned_to')->open(),
            'late' => $qr->late(),
            'to_review' => $qr->where('status', 'resolu'),
            'waiting' => $qr->where('status', 'en_attente'),
            default => $qr,
        };

        $workOrders = (clone $query)
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhereHas('room', fn ($r) => $r->where('number', 'like', "%{$q}%"))
            ))
            ->tap(fn ($qr) => $applyFilter($qr, $filter))
            ->when($request->filled('status'), fn ($qr) => $qr->where('status', $request->status))
            ->when($request->filled('priority_id'), fn ($qr) => $qr->where('priority_id', $request->priority_id))
            ->when($sort === 'priority', fn ($qr) => $qr->join('work_order_priorities', 'work_order_priorities.id', '=', 'work_orders.priority_id')
                ->orderBy('work_order_priorities.position')
                ->select('work_orders.*'))
            ->when($sort === 'created', fn ($qr) => $qr->latest('work_orders.created_at'))
            // Échéance : les OT encore à traiter d'abord (les plus en retard en tête), les terminés ensuite.
            ->when($sort === 'due', fn ($qr) => $qr
                ->orderByRaw('CASE WHEN status IN ('.implode(',', array_fill(0, count(WorkOrder::FINISHED_STATUSES), '?')).') THEN 1 ELSE 0 END', WorkOrder::FINISHED_STATUSES)
                ->orderByRaw('CASE WHEN sla_resolution_due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sla_resolution_due_at'))
            ->paginate(15)
            ->withQueryString();

        $sortOptions = ['due' => 'Échéance SLA', 'priority' => 'Priorité', 'created' => 'Date de création'];

        $isSupervisor = $authUser->role->seesAllWorkOrders();
        $filters = $isSupervisor
            ? [['key' => 'all', 'label' => 'Tous'], ['key' => 'urgent', 'label' => 'Urgents'], ['key' => 'unassigned', 'label' => 'Non affectés'], ['key' => 'late', 'label' => 'En retard SLA'], ['key' => 'to_review', 'label' => 'À contrôler'], ['key' => 'waiting', 'label' => 'En attente']]
            : [['key' => 'mine', 'label' => $authUser->role === UserRole::Technicien ? 'Mes ordres' : 'Mes signalements'], ['key' => 'urgent', 'label' => 'Urgents'], ['key' => 'all', 'label' => $authUser->isDepartmentHead() ? "Toute l'équipe" : 'Tous']];

        $count = fn (string $key) => $applyFilter(clone $query, $key)->count();
        $filterCounts = collect($filters)->mapWithKeys(fn ($f) => [$f['key'] => $count($f['key'])])->all();

        $total = $count('all');
        $open = $count('open');
        $stats = [
            ['label' => 'Total', 'value' => $total, 'sub' => 'tous statuts confondus', 'filter' => 'all', 'dot' => 'bg-navy'],
            ['label' => 'Ouverts', 'value' => $open, 'sub' => 'à traiter ou en cours', 'filter' => 'open', 'dot' => 'bg-blue'],
            ['label' => 'Non affectés', 'value' => $count('unassigned'), 'sub' => 'ouverts, sans technicien', 'filter' => 'unassigned', 'dot' => 'bg-gold'],
            ['label' => 'En retard SLA', 'value' => $late = $count('late'), 'sub' => $open ? round($late / $open * 100).' % des ordres ouverts' : 'aucun ordre ouvert', 'filter' => 'late', 'dot' => 'bg-red'],
        ];

        $priorities = WorkOrderPriority::where('is_active', true)->orderBy('position')->get();
        $statusLabels = WorkOrder::STATUS_LABELS;

        return view('work-orders.index', compact(
            'workOrders', 'q', 'filter', 'filters', 'filterCounts', 'stats', 'isSupervisor',
            'sort', 'sortOptions', 'priorities', 'statusLabels',
        ));
    }

    public function create(): View
    {
        // Lieux et équipements hors service ne sont plus proposés.
        $roomGroups = Room::groupedForSelect();
        $equipments = Equipment::forSelect();
        $technicians = User::activeTechnicians()->get();
        $types = WorkOrderType::where('is_active', true)->orderBy('position')->get();
        $priorities = WorkOrderPriority::where('is_active', true)->orderBy('position')->get();

        return view('work-orders.create', compact('roomGroups', 'equipments', 'technicians', 'types', 'priorities'));
    }

    public function store(StoreWorkOrderRequest $request, ReportWorkOrder $report): RedirectResponse
    {
        // Affectation et échéance relèvent du dispatch : ignorées si elles viennent d'un
        // service demandeur (champ absent du formulaire, mais une requête forgée pourrait l'envoyer).
        $data = $request->user()->role->dispatchesWork()
            ? $request->validated()
            : $request->safe()->except(['assigned_to', 'due_date']);

        $workOrder = $report->handle($request->user(), $data);

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'Ordre de travail créé avec succès.');
    }

    public function show(WorkOrder $workOrder): View
    {
        $this->authorize('view', $workOrder);
        $workOrder->load([
            'room', 'equipment', 'assignee', 'reporter', 'type', 'priority',
            'comments.user', 'attachments.uploader', 'statusHistories.changedBy',
            'partReservations.part', 'partReservations.reservedBy',
            'interventionSessions.technician', 'interventionReport',
            'qualityControls.reviewer', 'correctionRequests.requester',
            'maintenancePlan', 'slaPolicy',
        ]);

        return view('work-orders.show', compact('workOrder'));
    }

    public function edit(WorkOrder $workOrder): View
    {
        $this->authorize('update', $workOrder);
        // Le lieu / l'équipement actuel reste proposé même s'il est passé hors service.
        $roomGroups = Room::groupedForSelect($workOrder->room_id);
        $equipments = Equipment::forSelect($workOrder->equipment_id);
        $technicians = User::activeTechnicians($workOrder->assigned_to)->get();
        $types = WorkOrderType::where('is_active', true)->orderBy('position')->get();
        $priorities = WorkOrderPriority::where('is_active', true)->orderBy('position')->get();

        return view('work-orders.edit', compact('workOrder', 'roomGroups', 'equipments', 'technicians', 'types', 'priorities'));
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $workOrder->update($request->validated());
        $this->logEdit($workOrder);

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'Ordre de travail mis à jour avec succès.');
    }

    /**
     * Priorité, affectation, échéance, lieu... changent la lecture du SLA et la
     * responsabilité de l'OT : chaque modification est tracée, en clair.
     */
    private function logEdit(WorkOrder $workOrder): void
    {
        $labels = [
            'title' => 'titre', 'description' => 'description', 'priority_id' => 'priorité',
            'type_id' => 'type', 'assigned_to' => 'technicien', 'due_date' => 'échéance',
            'room_id' => 'lieu', 'equipment_id' => 'équipement',
        ];
        $readable = fn (string $field, mixed $value): string => match (true) {
            $value === null || $value === '' => '(vide)',
            $field === 'priority_id' => WorkOrderPriority::find($value)?->label ?? "#{$value}",
            $field === 'type_id' => WorkOrderType::find($value)?->label ?? "#{$value}",
            $field === 'assigned_to' => User::find($value)?->name ?? "#{$value}",
            $field === 'room_id' => Room::find($value)?->label ?? "#{$value}",
            $field === 'equipment_id' => Equipment::find($value)?->name ?? "#{$value}",
            $field === 'description' => '(texte modifié)',
            default => (string) $value,
        };

        $changes = collect($workOrder->getChanges())->only(array_keys($labels));
        if ($changes->isEmpty()) {
            return;
        }
        // Après save(), getOriginal() rend déjà les nouvelles valeurs : les anciennes sont ici.
        $previous = $workOrder->getPrevious();

        ActivityLog::record(
            'work_order.updated',
            "Modification de l'OT {$workOrder->code()} : ".$changes
                ->map(fn ($new, $field) => "{$labels[$field]} ".$readable($field, $previous[$field] ?? null).' → '.$readable($field, $new))
                ->implode(', '),
            $workOrder,
            $changes->map(fn ($new, $field) => ['from' => $previous[$field] ?? null, 'to' => $new])->all(),
        );
    }

    // Pas de destroy() : un OT ne se supprime pas, il s'annule (WorkOrderPilotController::cancel).

    public function updateStatus(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        // Avancement déclaré par l'intervenant ; les superviseurs pilotent ailleurs.
        $this->authorize('perform', $workOrder);
        $oldStatus = $workOrder->status;
        $newStatus = $request->validated('status');

        $workOrder->statusHistories()->create([
            'changed_by' => Auth::id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $request->validated('note'),
        ]);

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'en_cours' && is_null($workOrder->started_at)) {
            $updateData['started_at'] = now();
        }

        if (in_array($newStatus, ['resolu', 'ferme']) && is_null($workOrder->completed_at)) {
            $updateData['completed_at'] = now();
        }

        $workOrder->update($updateData);

        return back()->with('success', 'Statut mis à jour avec succès.');
    }

    public function storeComment(StoreWorkOrderCommentRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        $workOrder->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->validated('content'),
        ]);

        return back()->with('success', 'Commentaire ajouté avec succès.');
    }

    public function storeAttachments(StoreWorkOrderAttachmentRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        foreach ($request->file('files') as $file) {
            $path = $file->store('work-orders/' . $workOrder->id, FileDownloadController::DISK);

            $workOrder->attachments()->create([
                'uploaded_by' => Auth::id(),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Fichier(s) ajouté(s) avec succès.');
    }

    /** Retire le fichier de la fiche ; la ligne et le fichier restent (preuve), avec une trace au journal. */
    public function destroyAttachment(WorkOrder $workOrder, \App\Models\WorkOrderAttachment $attachment): RedirectResponse
    {
        $this->authorize('deleteAttachment', [$workOrder, $attachment]);
        $attachment->delete();

        ActivityLog::record(
            'work_order.attachment_deleted',
            "Pièce jointe « {$attachment->original_name} » retirée de l'OT {$workOrder->code()}",
            $workOrder,
            ['attachment_id' => $attachment->id, 'uploaded_by' => $attachment->uploaded_by],
        );

        return back()->with('success', 'Fichier retiré de la fiche.');
    }
}