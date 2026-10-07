<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\PartRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\PartRequestedNotification;
use App\Notifications\PartRequestHandledNotification;
use Illuminate\Http\RedirectResponse;
use App\Support\OpenAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * Pièce absente du magasin : le technicien la demande depuis son OT (et peut le
 * mettre en attente du même geste) ; le manager la commande ou la trouve, puis
 * marque la demande traitée, ce qui prévient le technicien.
 */
class PartRequestController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('requestPart', $workOrder);
        $data = $request->validate([
            'description' => ['required', 'string', 'max:200'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'suspend' => ['boolean'],
        ], [
            'description.required' => 'Décrivez la pièce (nom, marque, dimension…).',
        ]);
        $user = $request->user();

        $partRequest = $workOrder->partRequests()->create([
            'requested_by' => $user->id,
            'description' => trim($data['description']),
            'quantity' => $data['quantity'],
        ]);

        // « En attente de la pièce » : même trace que l'avancement déclaré à la main.
        if ($request->boolean('suspend') && in_array($workOrder->status, ['ouvert', 'en_cours'], true)) {
            $workOrder->statusHistories()->create([
                'changed_by' => $user->id,
                'old_status' => $workOrder->status,
                'new_status' => 'en_attente',
                'note' => "Pièce manquante : {$partRequest->quantity} × {$partRequest->description}.",
            ]);
            $workOrder->update(['status' => 'en_attente', 'acknowledged_at' => $workOrder->acknowledged_at ?? now()]);
        }

        Notification::send(self::stockKeepers(), new PartRequestedNotification($partRequest->load('workOrder.room', 'requester')));

        return back()->with('success', 'Demande envoyée au manager : vous serez prévenu quand la pièce sera là.');
    }

    public function handle(Request $request, PartRequest $partRequest): RedirectResponse
    {
        abort_unless(OpenAccess::enabled() || $request->user()->role?->dispatchesWork(), 403);
        abort_unless($partRequest->status === 'demandee', 422, 'Cette demande est déjà traitée.');
        $data = $request->validate([
            'handling_note' => ['nullable', 'string', 'max:300'],
        ]);

        $partRequest->update([
            'status' => 'traitee',
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
            'handling_note' => filled($data['handling_note'] ?? null) ? trim($data['handling_note']) : null,
        ]);

        $partRequest->requester?->notify(new PartRequestHandledNotification($partRequest->load('workOrder')));

        return back()->with('success', 'Demande traitée : le technicien est prévenu.');
    }

    /** Ceux qui gèrent le stock : les managers, à défaut les administrateurs. */
    private static function stockKeepers()
    {
        $managers = User::where('role', UserRole::Manager)->where('is_active', true)->get();

        return $managers->isNotEmpty()
            ? $managers
            : User::where('role', UserRole::Admin)->where('is_active', true)->get();
    }
}
