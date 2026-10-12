<?php

namespace App\Support;

use App\Models\WorkOrder;
use Illuminate\Support\Carbon;

/**
 * Le parcours d'un OT, le même pour tous les rôles (comme le suivi d'un colis) :
 * Signalé → Pris en charge → En réparation → Réparé → Contrôlé → Confirmé.
 *
 * « Confirmé » (le service demandeur dit que c'est réglé) n'existe que pour les OT
 * signalés par le Housekeeping ou la réception. Contrôle qualité et confirmation se
 * font en parallèle : une étape plus loin peut être faite avant la précédente.
 *
 * État de chaque étape : done (faite), current (là où en est l'OT), todo (à venir),
 * skipped (confirmation jamais donnée, délai passé). « alert » colore l'étape en cours
 * quand l'OT y est arrêté : en attente (ambre), correction demandée (rouge).
 */
class WorkOrderJourney
{
    /**
     * @return array<int, array{key: string, label: string, state: string, at: ?Carbon, meta: ?string, alert: ?array{0: string, 1: string}}>
     */
    public static function steps(WorkOrder $workOrder): array
    {
        $status = $workOrder->status;
        $repaired = in_array($status, ['resolu', 'ferme'], true);
        $closedAt = $workOrder->statusHistories->where('new_status', 'ferme')->sortByDesc('id')->first();
        $assignee = $workOrder->assignee;

        $steps = [
            ['key' => 'signale', 'label' => 'Signalé', 'reached' => true,
                'at' => $workOrder->created_at, 'meta' => $workOrder->reporter?->name],
            ['key' => 'pris_en_charge', 'label' => 'Pris en charge', 'reached' => $assignee !== null,
                'at' => $workOrder->acknowledged_at,
                'meta' => $assignee
                    ? $assignee->name.($workOrder->scheduled_at && ! $workOrder->started_at ? ' · prévu le '.$workOrder->scheduled_at->format('d/m à H\hi') : '')
                    : null],
            ['key' => 'en_reparation', 'label' => 'En réparation',
                'reached' => $workOrder->started_at !== null || in_array($status, ['en_cours', 'en_attente', 'rejete', 'resolu', 'ferme'], true),
                'at' => $workOrder->started_at, 'meta' => null],
            ['key' => 'repare', 'label' => 'Réparé', 'reached' => $repaired,
                'at' => $repaired ? $workOrder->completed_at : null, 'meta' => null],
            ['key' => 'controle', 'label' => 'Contrôlé', 'reached' => $status === 'ferme',
                'at' => $closedAt?->created_at, 'meta' => $status === 'ferme' ? $closedAt?->changedBy?->name : null],
        ];

        if (self::hasRequesterStep($workOrder)) {
            $steps[] = ['key' => 'confirme', 'label' => 'Confirmé', 'reached' => $workOrder->requester_confirmed_at !== null,
                'at' => $workOrder->requester_confirmed_at, 'meta' => $workOrder->requesterConfirmedBy?->name];
        }

        // Étape en cours : la dernière atteinte d'affilée depuis le début.
        $current = 0;
        while (isset($steps[$current + 1]) && $steps[$current + 1]['reached']) {
            $current++;
        }
        // Confirmation jamais donnée et délai passé : l'OT est fini quand même.
        $skipConfirmation = $status === 'ferme' && ($steps[5] ?? null) && ! $steps[5]['reached']
            && ! $workOrder->completed_at?->greaterThanOrEqualTo(now()->subDays(WorkOrder::CONFIRMATION_WINDOW_DAYS));
        $finished = $status === 'annule' || ($status === 'ferme' && ($current === count($steps) - 1 || $skipConfirmation));

        $alert = match ($status) {
            'en_attente' => ['En attente', WorkOrder::statusColor($status)],
            'rejete' => ['Correction demandée', WorkOrder::statusColor($status)],
            default => null,
        };

        return array_map(function (array $step, int $i) use ($current, $finished, $skipConfirmation, $alert, $status) {
            $state = match (true) {
                $step['key'] === 'confirme' && $skipConfirmation => 'skipped',
                $step['reached'] && ($i < $current || $finished) => 'done',
                $i === $current && $status !== 'annule' => 'current',
                $step['reached'] => 'done',
                default => 'todo',
            };
            // « Correction demandée » : l'OT est revenu de « Réparé » à la réparation.
            $alertHere = $state === 'current' && $alert !== null;
            $meta = $state === 'skipped' ? 'Pas de réponse du demandeur' : $step['meta'];

            return [
                'key' => $step['key'], 'label' => $step['label'], 'state' => $state,
                'at' => $step['at'], 'meta' => $meta, 'alert' => $alertHere ? $alert : null,
            ];
        }, $steps, array_keys($steps));
    }

    /** OT signalé par un service demandeur, qui confirme la réparation. */
    public static function hasRequesterStep(WorkOrder $workOrder): bool
    {
        return in_array($workOrder->reporter?->role?->value, ['housekeeping', 'reception'], true);
    }
}
