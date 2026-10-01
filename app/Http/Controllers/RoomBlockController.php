<?php

namespace App\Http\Controllers;

use App\Enums\RoomOccupancy;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\RoomBlockNotification;
use App\Support\ReceptionDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class RoomBlockController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', RoomBlock::class);
        $user = $request->user();
        $with = ['room', 'workOrder', 'requester', 'decider', 'releaser'];

        return view('room-blocks.index', [
            'pending' => RoomBlock::where('status', RoomBlock::REQUESTED)->with($with)->oldest()->get(),
            'blocked' => RoomBlock::where('status', RoomBlock::BLOCKED)->with($with)->oldest('decided_at')->get(),
            // Chambres libres (ou libérées aujourd'hui) en panne, sans blocage : à décider.
            'candidates' => $user->can('requestAny', RoomBlock::class)
                ? WorkOrder::visibleTo($user)->open()
                    ->whereIn('room_occupancy', [RoomOccupancy::Libre, RoomOccupancy::Depart])
                    ->whereHas('room', fn ($r) => $r->rooms()->whereDoesntHave('blocks', fn ($b) => $b->active()))
                    ->with('room', 'reporter', 'priority')->latest()->get()
                    ->filter(fn (WorkOrder $w) => $user->can('request', [RoomBlock::class, $w]))
                : collect(),
            // Clients concernés par une panne en cours : la réception gère (excuses, délogement).
            'guests' => WorkOrder::open()
                ->whereIn('room_occupancy', [RoomOccupancy::ClientAbsent, RoomOccupancy::ClientPresent])
                ->with('room', 'assignee')->orderByRaw('due_date IS NULL')->orderBy('due_date')->get(),
            'history' => RoomBlock::whereIn('status', [RoomBlock::REFUSED, RoomBlock::RELEASED])
                ->with($with)->latest('updated_at')->limit(15)->get(),
        ]);
    }

    public function store(WorkOrder $workOrder, Request $request): RedirectResponse
    {
        $this->authorize('request', [RoomBlock::class, $workOrder]);
        $room = $workOrder->room;

        if (! $room || $room->isCommonArea()) {
            return back()->with('error', 'Seule une chambre peut être bloquée à la vente.');
        }
        if ($room->activeBlock()) {
            return back()->with('warning', "{$room->label} a déjà une demande de blocage ou est déjà bloquée.");
        }

        $block = RoomBlock::create([
            'room_id' => $room->id,
            'work_order_id' => $workOrder->id,
            'status' => RoomBlock::REQUESTED,
            'reason' => $workOrder->title,
            'requested_by' => $request->user()->id,
        ]);

        Notification::send(ReceptionDesk::staff(), new RoomBlockNotification($block, RoomBlockNotification::REQUESTED));

        return back()->with('success', "Demande de blocage de {$room->label} envoyée à la réception.");
    }

    public function approve(RoomBlock $roomBlock, Request $request): RedirectResponse
    {
        $this->authorize('decide', $roomBlock);

        DB::transaction(function () use ($roomBlock, $request) {
            $roomBlock->update([
                'status' => RoomBlock::BLOCKED,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);
            // "maintenance" et non "hors_service" : la chambre reste un lieu actif où
            // l'on peut signaler et intervenir, elle n'est seulement plus vendue.
            $roomBlock->room->update(['status' => 'maintenance']);
        });

        $roomBlock->requester->notify(new RoomBlockNotification($roomBlock, RoomBlockNotification::APPROVED));

        return back()->with('success', "{$roomBlock->room->label} est bloquée. Pensez à la passer hors service dans Opera.");
    }

    public function refuse(RoomBlock $roomBlock, Request $request): RedirectResponse
    {
        $this->authorize('decide', $roomBlock);
        $note = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']])['decision_note'] ?? null;

        $roomBlock->update([
            'status' => RoomBlock::REFUSED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        $roomBlock->requester->notify(new RoomBlockNotification($roomBlock, RoomBlockNotification::REFUSED));

        return back()->with('success', "Blocage de {$roomBlock->room->label} refusé.");
    }

    public function release(RoomBlock $roomBlock, Request $request): RedirectResponse
    {
        $this->authorize('release', $roomBlock);

        DB::transaction(function () use ($roomBlock, $request) {
            $roomBlock->update([
                'status' => RoomBlock::RELEASED,
                'released_by' => $request->user()->id,
                'released_at' => now(),
            ]);
            $roomBlock->room->update(['status' => 'disponible']);
        });

        Notification::send(
            ReceptionDesk::staff()->reject(fn (User $u) => $u->id === $request->user()->id),
            new RoomBlockNotification($roomBlock, RoomBlockNotification::RELEASED)
        );

        return back()->with('success', "{$roomBlock->room->label} est remise en vente. La réception est prévenue.");
    }
}
