<?php

namespace App\Http\Controllers;

use App\Models\MaintenancePlan;
use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function admin(): View
    {
        return view('dashboards.admin', [
            'totalWorkOrders' => WorkOrder::count(),
            'openWorkOrders' => WorkOrder::open()->count(),
            'urgentOpen' => WorkOrder::open()->whereHas('priority', fn ($q) => $q->where('code', 'urgente'))->count(),
            'slaBreached' => WorkOrder::where('sla_breached', true)->whereNotIn('status', ['resolu', 'ferme'])->count(),
            'lowStockParts' => Part::whereColumn('quantity_on_hand', '<=', 'reorder_threshold')->where('is_active', true)->count(),
            'totalUsers' => User::where('is_active', true)->count(),
            'duePreventivePlans' => MaintenancePlan::active()->where('next_due_at', '<=', now()->addDays(7))->count(),
            'recentWorkOrders' => WorkOrder::with(['assignee', 'priority', 'type'])->latest()->take(6)->get(),
        ]);
    }

    public function manager(): View
    {
        return view('dashboards.manager', [
            'openWorkOrders' => WorkOrder::open()->count(),
            'urgentOpen' => WorkOrder::open()->whereHas('priority', fn ($q) => $q->where('code', 'urgente'))->count(),
            'awaitingQualityControl' => WorkOrder::where('status', 'resolu')->count(),
            'pendingPurchaseOrders' => PurchaseOrder::whereIn('status', ['brouillon', 'envoyee', 'confirmee'])->count(),
            'upcomingMaintenancePlans' => MaintenancePlan::active()
                ->with(['equipment', 'room'])
                ->where('next_due_at', '<=', now()->addDays(14))
                ->orderBy('next_due_at')
                ->take(6)
                ->get(),
            'technicianWorkload' => User::where('role', 'technicien')
                ->where('is_active', true)
                ->withCount(['assignedWorkOrders as open_count' => fn ($q) => $q->open()])
                ->orderByDesc('open_count')
                ->get(),
            'recentWorkOrders' => WorkOrder::with(['assignee', 'priority', 'reporter'])->latest()->take(6)->get(),
        ]);
    }

    public function technicien(): View
    {
        $user = Auth::user();

        return view('dashboards.technicien', [
            'myOpenWorkOrders' => WorkOrder::where('assigned_to', $user->id)
                ->open()
                ->with(['room', 'priority'])
                ->orderByRaw("FIELD((SELECT code FROM work_order_priorities WHERE id = priority_id), 'urgente', 'haute', 'moyenne', 'basse')")
                ->get(),
            'todayScheduled' => WorkOrder::where('assigned_to', $user->id)
                ->whereDate('scheduled_at', today())
                ->with(['room', 'priority'])
                ->orderBy('scheduled_at')
                ->get(),
            'activeSession' => WorkOrder::where('assigned_to', $user->id)
                ->whereHas('interventionSessions', fn ($q) => $q->whereNull('ended_at'))
                ->first(),
            'completedThisWeek' => WorkOrder::where('assigned_to', $user->id)
                ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ]);
    }

    public function housekeeping(): View
    {
        return $this->reporterDashboard('housekeeping');
    }

    public function reception(): View
    {
        return $this->reporterDashboard('reception');
    }

    /**
     * Dashboard commun aux rôles "demandeurs" (housekeeping, réception) :
     * ils suivent leurs propres signalements plutôt que d'exécuter des OT.
     */
    private function reporterDashboard(string $view): View
    {
        $user = Auth::user();

        return view("dashboards.{$view}", [
            'myReports' => WorkOrder::where('reported_by', $user->id)
                ->with(['room', 'priority', 'assignee'])
                ->latest()
                ->take(10)
                ->get(),
            'myOpenCount' => WorkOrder::where('reported_by', $user->id)->open()->count(),
            'myResolvedCount' => WorkOrder::where('reported_by', $user->id)->whereIn('status', ['resolu', 'ferme'])->count(),
        ]);
    }
}