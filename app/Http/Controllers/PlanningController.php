<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ScheduleWorkOrderRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderScheduledNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlanningController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role === UserRole::Technicien) {
            return redirect()->route('planning.technician', $user);
        }

        $technicians = User::activeTechnicians()->get();

        return view('planning.index', compact('technicians'));
    }

    public function byTechnician(User $technician): View
    {
        abort_if($technician->role !== UserRole::Technicien, 404);

        /** @var User $user */
        $user = Auth::user();

        if ($user->role === UserRole::Technicien && $user->id !== $technician->id) {
            abort(403, 'Vous ne pouvez consulter que votre propre planning.');
        }

        return view('planning.technician', compact('technician'));
    }

    /**
     * Interventions planifiées entre deux dates, pour l'agenda (resources/js/agenda.js).
     * Au-delà du titre et des horaires : de quoi afficher une carte complète sans
     * ouvrir l'OT (référence, lieu, technicien, statut, priorité, durée).
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'technician_id' => ['nullable', 'integer'],
        ]);

        $query = WorkOrder::with(['room', 'equipment', 'assignee', 'priority'])
            ->scheduledBetween($validated['start'], $validated['end']);

        /** @var User $user */
        $user = Auth::user();

        if ($user->role === UserRole::Technicien) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('technician_id')) {
            $query->where('assigned_to', $request->query('technician_id'));
        }

        $events = $query->orderBy('scheduled_at')->get()->map(fn (WorkOrder $workOrder) => [
            'id' => $workOrder->id,
            'code' => $workOrder->code(),
            'title' => $workOrder->title,
            'start' => $workOrder->scheduled_at->toIso8601String(),
            'end' => $workOrder->scheduled_end_at?->toIso8601String(),
            'minutes' => $workOrder->estimated_duration_minutes,
            'url' => route('work-orders.show', $workOrder),
            'color' => $workOrder->priority->color,
            'priority' => $workOrder->priority->label,
            'urgent' => $workOrder->priority->code === 'urgente',
            'status' => $workOrder->status,
            'status_label' => $workOrder->status_label,
            'place' => $workOrder->room?->label ?? $workOrder->equipment?->name,
            'technician' => $workOrder->assignee?->name,
            'initials' => $workOrder->assignee?->initialsOrGenerated(),
        ]);

        return response()->json($events);
    }

    public function schedule(WorkOrder $workOrder): View
    {
        $technicians = User::activeTechnicians()->with('skills')->get();

        return view('planning.schedule', compact('workOrder', 'technicians'));
    }

    public function storeSchedule(ScheduleWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $technician = User::findOrFail($request->validated('assigned_to'));
        $scheduledAt = \Carbon\Carbon::parse($request->validated('scheduled_at'));

        $isReschedule = ! is_null($workOrder->scheduled_at);
        $isAvailable = $technician->isAvailableAt($scheduledAt);

        $workOrder->update([
            'assigned_to' => $technician->id,
            'scheduled_at' => $scheduledAt,
            'estimated_duration_minutes' => $request->validated('estimated_duration_minutes'),
            'scheduled_by' => Auth::id(),
        ]);

        $technician->notify(new WorkOrderScheduledNotification($workOrder->fresh(), $isReschedule));

        if (! $isAvailable) {
            return redirect()->route('work-orders.show', $workOrder)
                ->with('warning', 'Attention : ce technicien n\'a pas de créneau de disponibilité déclaré à cette date/heure. Planification tout de même enregistrée.');
        }

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'Ordre de travail planifié avec succès pour ' . $technician->name . '.');
    }
}