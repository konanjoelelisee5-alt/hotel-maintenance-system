<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lieux de l'hôtel (chambres et espaces communs) : gérés par les admins et les
 * managers. Pas de suppression : un lieu est mis hors service, son historique
 * d'OT reste consultable.
 */
class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::query()
            ->withCount(['equipment', 'workOrders as open_work_orders_count' => fn ($q) => $q->open()])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('floor'), fn ($q) => $q->where('floor', $request->floor))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$request->search.'%')
                ->orWhere('name', 'like', '%'.$request->search.'%')))
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        $floors = Room::whereNotNull('floor')->distinct()->orderBy('floor')->pluck('floor');

        return view('rooms.index', compact('rooms', 'floors'));
    }

    public function create(Request $request): View
    {
        // ?type=espace_commun : le bouton « + Espace commun » pré-sélectionne le type.
        return view('rooms.create', ['room' => new Room(['type' => $request->query('type', Room::TYPE_ROOM)])]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $room = Room::create($this->clean($request->validated()));

        return redirect()->route('rooms.show', $room)->with('success', "{$room->label} ajouté(e).");
    }

    public function show(Room $room): View
    {
        $room->load(['equipment' => fn ($q) => $q->orderBy('name')]);

        $workOrders = $room->workOrders()->with(['priority', 'assignee', 'equipment'])->latest()->paginate(10);

        // Repère les lieux à problème : beaucoup de pannes récentes = remplacer
        // plutôt que réparer encore.
        $stats = [
            'open' => $room->workOrders()->open()->count(),
            'last90' => $room->workOrders()->where('created_at', '>=', now()->subDays(90))->count(),
            'total' => $room->workOrders()->count(),
        ];

        return view('rooms.show', compact('room', 'workOrders', 'stats'));
    }

    public function edit(Room $room): View
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(StoreRoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($this->clean($request->validated()));

        return redirect()->route('rooms.show', $room)->with('success', "{$room->label} mis(e) à jour.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        $room->update(['status' => 'hors_service']);

        $open = $room->workOrders()->open()->count();

        return redirect()->route('rooms.show', $room)->with(
            $open > 0 ? 'warning' : 'success',
            "{$room->label} mis(e) hors service."
                .($open > 0 ? " Attention : {$open} ordre(s) de travail encore ouvert(s) sur ce lieu." : '')
        );
    }

    /** Une chambre n'a pas de nom propre : on l'efface si le type a changé. */
    private function clean(array $data): array
    {
        if ($data['type'] === Room::TYPE_ROOM) {
            $data['name'] = null;
        }

        return $data;
    }
}
