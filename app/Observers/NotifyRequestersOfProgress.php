<?php

namespace App\Observers;

use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderProgressNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Tient le service demandeur au courant : l'agent qui a signalé et le responsable
 * de son service (la gouvernante pour le housekeeping) sont prévenus quand un
 * technicien est affecté, puis quand la panne est réparée.
 *
 * Branché sur le modèle plutôt que dans chaque contrôleur : l'affectation vient
 * du planning ou du formulaire, la résolution du statut ou du rapport signé.
 */
class NotifyRequestersOfProgress
{
    public function updated(WorkOrder $workOrder): void
    {
        $step = $this->step($workOrder);

        if ($step === null) {
            return;
        }

        $recipients = $this->requesters($workOrder);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new WorkOrderProgressNotification(
                $workOrder->loadMissing('room', 'assignee'),
                $step,
            ));
        }
    }

    private function step(WorkOrder $workOrder): ?string
    {
        if ($workOrder->wasChanged('status')) {
            $old = $workOrder->getOriginal('status');

            // Fermé depuis "résolu" : la réparation a déjà été annoncée (et le
            // contrôle qualité prévient lui-même l'agent de la clôture).
            if ($workOrder->status === 'resolu' || ($workOrder->status === 'ferme' && $old !== 'resolu')) {
                return WorkOrderProgressNotification::RESOLVED;
            }
        }

        if ($workOrder->wasChanged('assigned_to') && $workOrder->assigned_to !== null) {
            return WorkOrderProgressNotification::ASSIGNED;
        }

        return null;
    }

    /**
     * @return Collection<int, User>
     */
    private function requesters(WorkOrder $workOrder): Collection
    {
        $reporter = $workOrder->reporter;

        if ($reporter === null) {
            return collect();
        }

        $heads = $reporter->role?->hasDepartmentHead()
            ? User::where('role', $reporter->role)->where('is_department_head', true)->where('is_active', true)->get()
            : collect();

        return collect([$reporter])
            ->merge($heads)
            ->unique('id')
            ->filter(fn (User $u) => $u->is_active)
            // Celui qui vient d'agir n'a pas besoin qu'on le lui annonce.
            ->reject(fn (User $u) => $u->id === Auth::id())
            ->values();
    }
}
