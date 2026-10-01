<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Actions de pilotage d'un OT par un superviseur (admin, manager) :
 * suspendre, relancer, annuler. Chacune exige ou trace un motif, pour que
 * l'historique de l'OT explique toujours pourquoi il a changé d'état.
 *
 * Affecter/planifier (PlanningController), requalifier (WorkOrderController::edit),
 * contrôle qualité puis fermeture (QualityControlController) complètent le panneau.
 */
class WorkOrderPilotController extends Controller
{
    public function suspend(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('suspend', $workOrder);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:500']], $this->messages())['reason'];

        $this->transition($workOrder, 'en_attente', "Mis en attente : {$reason}");

        return back()->with('success', 'OT mis en attente.');
    }

    public function resume(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('resume', $workOrder);

        // Repart là où il en était : en cours s'il avait déjà commencé, sinon ouvert.
        $this->transition($workOrder, $workOrder->started_at ? 'en_cours' : 'ouvert', 'Relancé : la cause du blocage est levée.');

        return back()->with('success', 'OT relancé.');
    }

    public function cancel(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('cancel', $workOrder);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:500']], $this->messages())['reason'];

        $this->transition($workOrder, 'annule', "Annulé : {$reason}");

        // Un chrono resté ouvert sur un OT annulé fausserait les temps passés.
        $workOrder->activeSession()?->stop();

        ActivityLog::record('work_order.cancelled', "Annulation de l'OT {$workOrder->code()} « {$workOrder->title} » : {$reason}", $workOrder);

        return redirect()->route('work-orders.show', $workOrder)->with('success', 'OT annulé. Il reste consultable dans l\'historique.');
    }

    private function transition(WorkOrder $workOrder, string $status, string $note): void
    {
        $workOrder->statusHistories()->create([
            'changed_by' => Auth::id(),
            'old_status' => $workOrder->status,
            'new_status' => $status,
            'note' => $note,
        ]);

        $workOrder->update(['status' => $status]);
    }

    private function messages(): array
    {
        return ['reason.required' => 'Indiquez le motif : il sera visible dans l\'historique de l\'OT.'];
    }
}
