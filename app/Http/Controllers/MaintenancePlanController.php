<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenancePlanRequest;
use App\Http\Requests\UpdateMaintenancePlanRequest;
use App\Models\ChecklistTemplate;
use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\Room;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenancePlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = MaintenancePlan::with(['room', 'equipment', 'type', 'priority', 'assignee'])
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'actif'))
            ->orderBy('next_due_at')
            ->paginate(15)
            ->withQueryString();

        return view('maintenance-plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('maintenance-plans.create', $this->formData());
    }

    public function store(StoreMaintenancePlanRequest $request): RedirectResponse
    {
        $plan = MaintenancePlan::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('maintenance-plans.show', $plan)
            ->with('success', 'Plan de maintenance préventive créé avec succès.');
    }

    public function show(MaintenancePlan $maintenancePlan): View
    {
        $maintenancePlan->load(['room', 'equipment', 'type', 'priority', 'checklistTemplate', 'assignee', 'requiredSkill', 'creator']);
        $recentWorkOrders = $maintenancePlan->workOrders()->with(['assignee', 'priority'])->take(10)->get();

        return view('maintenance-plans.show', compact('maintenancePlan', 'recentWorkOrders'));
    }

    public function edit(MaintenancePlan $maintenancePlan): View
    {
        return view('maintenance-plans.edit', [
            'maintenancePlan' => $maintenancePlan,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        // Si la date de début est repoussée au-delà de l'échéance déjà calculée, on la réaligne
        // pour éviter de générer un OT rétroactivement.
        $newStartDate = Carbon::parse($data['start_date']);
        if (is_null($maintenancePlan->next_due_at) || $newStartDate->gt($maintenancePlan->next_due_at)) {
            $data['next_due_at'] = $newStartDate;
        }

        $maintenancePlan->update($data);

        return redirect()->route('maintenance-plans.show', $maintenancePlan)
            ->with('success', 'Plan de maintenance préventive mis à jour avec succès.');
    }

    public function destroy(MaintenancePlan $maintenancePlan): RedirectResponse
    {
        $maintenancePlan->delete();

        return redirect()->route('maintenance-plans.index')
            ->with('success', 'Plan de maintenance préventive supprimé avec succès.');
    }

    /**
     * Déclenchement manuel immédiat : génère un OT à partir de ce plan sans attendre
     * son échéance planifiée (utile pour un besoin ponctuel ou pour tester le plan).
     */
    public function generateNow(MaintenancePlan $maintenancePlan): RedirectResponse
    {
        if (! $maintenancePlan->is_active) {
            return back()->with('error', 'Ce plan est inactif : réactivez-le avant de générer un OT.');
        }

        $workOrder = $maintenancePlan->generateWorkOrder();

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', "Ordre de travail #{$workOrder->id} généré manuellement à partir de ce plan.");
    }

    private function formData(): array
    {
        return [
            'rooms' => Room::orderBy('number')->get(),
            'equipments' => Equipment::orderBy('name')->get(),
            'types' => WorkOrderType::where('is_active', true)->orderBy('position')->get(),
            'priorities' => WorkOrderPriority::where('is_active', true)->orderBy('position')->get(),
            'templates' => ChecklistTemplate::orderBy('name')->get(),
            'technicians' => User::where('role', 'technicien')->where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
