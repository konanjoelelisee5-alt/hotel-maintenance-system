<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * « Je m'en charge » : un admin ou un manager (le chef de maintenance, un soir
 * sans technicien disponible) devient l'intervenant de l'OT. C'est la seule façon
 * pour lui d'utiliser le chrono et le rapport : son nom y apparaît honnêtement, et
 * il ne pourra pas faire le contrôle qualité de ce travail.
 */
class WorkOrderTakeOverController extends Controller
{
    public function __invoke(WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('takeOver', $workOrder);

        $user = Auth::user();
        $previous = $workOrder->assignee;

        $workOrder->update(['assigned_to' => $user->id]);

        $workOrder->statusHistories()->create([
            'changed_by' => $user->id,
            'old_status' => $workOrder->status,
            'new_status' => $workOrder->status,
            'note' => "Pris en charge par {$user->name} ({$user->role_label})"
                .($previous ? ", à la place de {$previous->name}." : '.'),
        ]);

        return redirect()->route('work-orders.show', $workOrder)
            ->with('success', 'Vous êtes maintenant l\'intervenant de cet OT : chrono et rapport sont à votre nom. Le contrôle qualité sera fait par un autre responsable.');
    }
}
