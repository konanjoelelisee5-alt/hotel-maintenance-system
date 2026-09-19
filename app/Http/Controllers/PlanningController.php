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

        $technicians = User::where('role', 'technicien')->orderBy('name')->get();

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

    public function events(Request $request): JsonResponse
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $query = WorkOrder::with(['room', 'assignee', 'priority'])
            ->scheduledBetween($start, $end);

        /** @var User $user */
        $user = Auth::user();

        if ($user->role === UserRole::Technicien) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('technician_id')) {
            $query->where('assigned_to', $request->query('technician_id'));
        }

        $workOrders = $query->get();

        $events = $workOrders->map(function (WorkOrder $workOrder) {
            return [
                'id' => $workOrder->id,
                'title' => $workOrder->title . ($workOrder->assignee ? ' — ' . $workOrder->assignee->name : ''),
                'start' => $workOrder->scheduled_at->toIso8601String(),
                'end' => $workOrder->scheduled_end_at?->toIso8601String(),
                'url' => route('work-orders.show', $workOrder),
                'color' => $workOrder->priority->color,
            ];
        });

        return response()->json($events);
    }

    public function schedule(WorkOrder $workOrder): View
    {
        $technicians = User::where('role', 'technicien')
            ->with('skills')
            ->orderBy('name')
            ->get();

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