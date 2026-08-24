<?php

namespace App\Http\Controllers;

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
        $query = WorkOrder::with(['room', 'equipment', 'assignee', 'reporter', 'type', 'priority']);

        // Un technicien ne voit que ses OT assignés ; housekeeping/réception, ceux qu'ils ont signalés
        if (Auth::user()->role === 'technicien') {
            $query->where('assigned_to', Auth::id());
        } elseif (in_array(Auth::user()->role, ['housekeeping', 'reception'])) {
            $query->where('reported_by', Auth::id());
        }

        $workOrders = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority_id'), fn ($q) => $q->where('priority_id', $request->priority_id))
            ->latest()
            ->paginate(15);

        return view('work-orders.index', compact('workOrders'));
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