<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\InterventionSession;
use App\Models\MaintenancePlan;
use App\Models\User;
use App\Notifications\WorkOrderReassignedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Départ d'un employé : aucun travail ne doit rester "orphelin".
 *
 *  1. chaque OT ouvert qui lui est assigné passe au remplaçant choisi pour cet OT,
 *     sinon au remplaçant par défaut, sinon redevient "non affecté" (file du manager) ;
 *  2. ses plans de maintenance préventive passent au remplaçant par défaut ;
 *  3. un chronomètre d'intervention resté ouvert est arrêté ;
 *  4. le compte est désactivé, et tout est tracé (historique de l'OT + journal).
 *
 * Tout ou rien (transaction) : jamais un compte désactivé avec la moitié de ses OT.
 */
class DeactivateUser
{
    /**
     * @param  array<int|string, int|string|null>  $overrides  [work_order_id => technicien choisi pour cet OT]
     * @return array{reassigned: int, unassigned: int, plans: int}
     */
    public function handle(User $leaving, ?int $defaultReplacementId, array $overrides = []): array
    {
        $summary = ['reassigned' => 0, 'unassigned' => 0, 'plans' => 0];
        $notifications = [];

        DB::transaction(function () use ($leaving, $defaultReplacementId, $overrides, &$summary, &$notifications) {
            $replacements = User::whereKey(array_filter([$defaultReplacementId, ...array_values($overrides)]))->get()->keyBy('id');

            foreach ($leaving->assignedWorkOrders()->open()->with('room')->get() as $workOrder) {
                $targetId = ((int) ($overrides[$workOrder->id] ?? 0)) ?: $defaultReplacementId;
                $target = $targetId ? $replacements->get($targetId) : null;

                $workOrder->update(['assigned_to' => $target?->id]);

                // Trace dans l'historique de l'OT (le statut ne change pas).
                $workOrder->statusHistories()->create([
                    'changed_by' => Auth::id(),
                    'old_status' => $workOrder->status,
                    'new_status' => $workOrder->status,
                    'note' => $target
                        ? "Réaffecté de {$leaving->name} à {$target->name} (départ de {$leaving->name})."
                        : "Désaffecté suite au départ de {$leaving->name} : à réaffecter.",
                ]);

                if ($target) {
                    $summary['reassigned']++;
                    $notifications[] = [$target, $workOrder];
                } else {
                    $summary['unassigned']++;
                }
            }

            // Sans remplaçant, le plan repasse en affectation automatique par compétence.
            $summary['plans'] = MaintenancePlan::where('assigned_to', $leaving->id)
                ->update(['assigned_to' => $defaultReplacementId]);

            InterventionSession::where('technician_id', $leaving->id)->whereNull('ended_at')->get()
                ->each(fn (InterventionSession $session) => $session->stop());

            $leaving->update(['is_active' => false]);

            ActivityLog::record(
                'user.deactivated',
                "Désactivation de l'utilisateur {$leaving->name}"
                    .($summary['reassigned'] + $summary['unassigned'] > 0
                        ? " : {$summary['reassigned']} OT réaffecté(s), {$summary['unassigned']} remis en attente d'affectation"
                        : ''),
                $leaving,
                $summary,
            );
        });

        // Après la transaction : on ne prévient personne d'un changement annulé.
        foreach ($notifications as [$target, $workOrder]) {
            $target->notify(new WorkOrderReassignedNotification($workOrder, $leaving->name));
        }

        return $summary;
    }
}
