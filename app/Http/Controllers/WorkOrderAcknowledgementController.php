<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderAcknowledgedNotification;
use App\Support\OnCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * « J'ai vu, je m'en occupe » : le technicien affecté confirme qu'il a vu l'OT. Le
 * manager le lit sur la fiche et dans les listes ; pour une urgence, l'astreinte est
 * prévenue (elle attend de savoir si quelqu'un y va).
 */
class WorkOrderAcknowledgementController extends Controller
{
    public function __invoke(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('acknowledge', $workOrder);
        $user = Auth::user();

        $workOrder->update(['acknowledged_at' => now()]);
        $workOrder->statusHistories()->create([
            'changed_by' => $user->id,
            'old_status' => $workOrder->status,
            'new_status' => $workOrder->status,
            'note' => "Vu par {$user->name} : je m'en occupe.",
        ]);

        if ($workOrder->priority?->code === 'urgente') {
            Notification::send(
                OnCall::recipients()->reject(fn (User $u) => $u->id === $user->id),
                new WorkOrderAcknowledgedNotification($workOrder->load('room'), $user->name),
            );
        }

        return back()->with('success', 'C\'est noté : le manager voit que vous vous en occupez.');
    }
}
