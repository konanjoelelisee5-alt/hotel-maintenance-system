<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Equipment;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Équipements de l'hôtel, rattachés à un lieu (chambre ou espace commun).
 * Pas de suppression : un équipement remplacé est mis hors service et garde
 * l'historique de ses pannes.
 */
class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $equipment = Equipment::query()
            ->with('room')
            ->withCount(['workOrders as open_work_orders_count' => fn ($q) => $q->open()])
            ->when($request->filled('room_id'), fn ($q) => $q->where('room_id', $request->room_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $types = Equipment::whereNotNull('type')->distinct()->orderBy('type')->pluck('type');
        $rooms = Room::ordered()->get();

        return view('equipment.index', compact('equipment', 'types', 'rooms'));
    }

    public function create(Request $request): View
    {
        // ?room_id=… : bouton « + Ajouter un équipement » depuis la fiche d'un lieu.
        return view('equipment.create', [
            'equipment' => new Equipment(['room_id' => $request->query('room_id')]),
            'roomGroups' => Room::groupedForSelect(),
            'types' => Equipment::whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $equipment = Equipment::create($request->validated());

        return redirect()->route('equipment.show', $equipment)->with('success', "Équipement « {$equipment->name} » ajouté.");
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load('room', 'maintenancePlans');

        $workOrders = $equipment->workOrders()->with(['priority', 'assignee'])->latest()->paginate(10);

        $stats = [
            'open' => $equipment->workOrders()->open()->count(),
            'last90' => $equipment->workOrders()->where('created_at', '>=', now()->subDays(90))->count(),
            'total' => $equipment->workOrders()->count(),
        ];

        return view('equipment.show', compact('equipment', 'workOrders', 'stats'));
    }

    public function edit(Equipment $equipment): View
    {
        return view('equipment.edit', [
            'equipment' => $equipment,
            'roomGroups' => Room::groupedForSelect($equipment->room_id),
            'types' => Equipment::whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function update(StoreEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $equipment->update($request->validated());

        return redirect()->route('equipment.show', $equipment)->with('success', "Équipement « {$equipment->name} » mis à jour.");
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $equipment->update(['status' => 'hors_service']);

        $activePlans = $equipment->maintenancePlans()->active()->count();

        return redirect()->route('equipment.show', $equipment)->with(
            $activePlans > 0 ? 'warning' : 'success',
            "Équipement « {$equipment->name} » mis hors service."
                .($activePlans > 0 ? " Attention : {$activePlans} plan(s) de maintenance préventive encore actif(s) sur cet équipement." : '')
        );
    }
}
