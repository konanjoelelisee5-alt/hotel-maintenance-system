<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreWorkOrderAttachmentRequest;
use App\Http\Requests\StoreWorkOrderCommentRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderStatusRequest;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

        $workOrders = (clone $query)
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhereHas('room', fn ($r) => $r->where('number', 'like', "%{$q}%"))
            ))
            ->when($filter === 'urgent', fn ($qr) => $qr->whereHas('priority', fn ($p) => $p->where('code', 'urgente')))
            ->when($filter === 'unassigned', fn ($qr) => $qr->whereNull('assigned_to'))
            ->when($filter === 'late', fn ($qr) => $qr->where('sla_breached', true))
            ->when($request->filled('status'), fn ($qr) => $qr->where('status', $request->status))
            ->when($request->filled('priority_id'), fn ($qr) => $qr->where('priority_id', $request->priority_id))
            ->when($sort === 'priority', fn ($qr) => $qr->join('work_order_priorities', 'work_order_priorities.id', '=', 'work_orders.priority_id')
                ->orderBy('work_order_priorities.position')
                ->select('work_orders.*'))
            ->when($sort === 'created', fn ($qr) => $qr->latest('work_orders.created_at'))
            ->when($sort === 'due', fn ($qr) => $qr->orderByRaw('CASE WHEN sla_breached THEN 0 ELSE 1 END')->orderBy('sla_resolution_due_at'))
            ->paginate(15)
            ->withQueryString();

        $sortOptions = ['due' => 'Échéance SLA', 'priority' => 'Priorité', 'created' => 'Date de création'];

        $isSupervisor = $authUser->role->seesAllWorkOrders();
        $filters = $isSupervisor
            ? [['key' => 'all', 'label' => 'Tous'], ['key' => 'urgent', 'label' => 'Urgents'], ['key' => 'unassigned', 'label' => 'Non affectés'], ['key' => 'late', 'label' => 'En retard SLA']]
            : [['key' => 'mine', 'label' => $authUser->role === UserRole::Technicien ? 'Mes ordres' : 'Mes signalements'], ['key' => 'urgent', 'label' => 'Urgents'], ['key' => 'all', 'label' => 'Tous']];

        $stats = [
            ['label' => 'Total', 'value' => (clone $query)->count()],
            ['label' => 'Ouverts', 'value' => (clone $query)->open()->count()],
            ['label' => 'Non affectés', 'value' => (clone $query)->whereNull('assigned_to')->open()->count()],
            ['label' => 'SLA dépassé', 'value' => (clone $query)->where('sla_breached', true)->count()],
        ];

        return view('work-orders.index', compact('workOrders', 'q', 'filter', 'filters', 'stats', 'isSupervisor', 'sort', 'sortOptions'));
    }

    public function create(): View
    {
        $rooms = Room::orderBy('number')->get();
        $equipments = Equipment::orderBy('name')->get();
        $technicians = User::where('role', 'technicien')->orderBy('name')->get();
        $types = WorkOrderType::where('is_active', true)->orderBy('position')->get();
        $priorities = WorkOrderPriority::where('is_active', true)->orderBy('position')->get();

        return view('work-orders.create', compact('rooms', 'equipments', 'technicians', 'types', 'priorities'));
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $workOrder = WorkOrder::create([
            ...$request->validated(),
            'reported_by' => Auth::id(),
            'status' => 'ouvert',
        ]);

        $workOrder->statusHistories()->create([
            'changed_by' => Auth::id(),
            'old_status' => null,
            'new_status' => 'ouvert',
            'note' => 'Création de l\'ordre de travail.',
        ]);

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
        $rooms = Room::orderBy('number')->get();
        $equipments = Equipment::orderBy('name')->get();
        $technicians = User::where('role', 'technicien')->orderBy('name')->get();
        $types = WorkOrderType::where('is_active', true)->orderBy('position')->get();
        $priorities = WorkOrderPriority::where('is_active', true)->orderBy('position')->get();

        return view('work-orders.edit', compact('workOrder', 'rooms', 'equipments', 'technicians', 'types', 'priorities'));
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('update', $workOrder);
        $workOrder->update($request->validated());

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'Ordre de travail mis à jour avec succès.');
    }

    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('delete', $workOrder);
        foreach ($workOrder->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $workOrder->delete();

        return redirect()->route('work-orders.index')
            ->with('success', 'Ordre de travail supprimé avec succès.');
    }

    public function updateStatus(UpdateWorkOrderStatusRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
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
            $path = $file->store('work-orders/' . $workOrder->id, 'public');

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

    public function destroyAttachment(WorkOrder $workOrder, \App\Models\WorkOrderAttachment $attachment): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Fichier supprimé avec succès.');
    }
}