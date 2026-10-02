<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderReopenedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Dernier mot du service demandeur : « c'est réglé » (la réparation est confirmée),
 * ou « toujours en panne » (l'OT est rouvert avec un motif, et la maintenance prévenue).
 */
class WorkOrderConfirmationController extends Controller
{
    public function confirm(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('confirmResolution', $workOrder);

        $workOrder->update([
            'requester_confirmed_at' => now(),
            'requester_confirmed_by' => $request->user()->id,
        ]);

        ActivityLog::record('work_order.requester_confirmed', "Réparation de l'OT {$workOrder->code()} confirmée par le demandeur", $workOrder);

        return back()->with('success', 'Merci : la réparation est confirmée.');
    }

    public function reopen(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('confirmResolution', $workOrder);
        $reason = $request->validate(
            ['reason' => ['required', 'string', 'max:500']],
            ['reason.required' => 'Décrivez ce qui ne va pas : le technicien en a besoin pour revenir.'],
        )['reason'];

        DB::transaction(function () use ($request, $workOrder, $reason) {
            // Repart chez le même technicien s'il y en avait un.
            $status = $workOrder->assigned_to ? 'en_cours' : 'ouvert';

            $workOrder->statusHistories()->create([
                'changed_by' => $request->user()->id,
                'old_status' => $workOrder->status,
                'new_status' => $status,
                'note' => "Rouvert par le demandeur : {$reason}",
            ]);
            $workOrder->comments()->create(['user_id' => $request->user()->id, 'content' => "Toujours en panne : {$reason}"]);
            $workOrder->update(['status' => $status, 'completed_at' => null]);
        });

        ActivityLog::record('work_order.reopened', "OT {$workOrder->code()} rouvert par le demandeur : {$reason}", $workOrder);

        $recipients = collect([$workOrder->assignee])
            ->merge(User::maintenanceAlertRecipients(UserRole::Manager)->get())
            ->merge(User::maintenanceAlertRecipients(UserRole::Admin)->get())
            ->filter(fn (?User $u) => $u?->is_active)
            ->unique('id');
        Notification::send($recipients, new WorkOrderReopenedNotification($workOrder->loadMissing('room'), $request->user(), $reason));

        return back()->with('success', "L'OT est rouvert : la maintenance est prévenue.");
    }
}
