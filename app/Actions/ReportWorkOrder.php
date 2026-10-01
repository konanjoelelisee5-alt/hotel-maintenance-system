<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\PriorityWorkOrderCreatedNotification;
use App\Support\OnCall;
use Illuminate\Support\Facades\Notification;

/**
 * Enregistre un signalement humain (formulaire complet ou signalement rapide) :
 * OT ouvert, première ligne d'historique, alerte d'astreinte si c'est grave.
 */
class ReportWorkOrder
{
    public function handle(User $reporter, array $attributes): WorkOrder
    {
        $workOrder = WorkOrder::create([
            ...$attributes,
            'reported_by' => $reporter->id,
            'status' => 'ouvert',
        ]);

        $workOrder->statusHistories()->create([
            'changed_by' => $reporter->id,
            'old_status' => null,
            'new_status' => 'ouvert',
            'note' => 'Création de l\'ordre de travail.',
        ]);

        // Signalement grave : on prévient l'équipe d'astreinte tout de suite, sans
        // attendre qu'un manager ouvre son tableau de bord. Fait ici (et non sur
        // l'évènement "created" du modèle) pour ne viser que les signalements
        // humains : les OT préventifs générés à 5 h sont planifiés, pas urgents.
        if ($workOrder->priority?->triggersOnCallAlert()) {
            Notification::send(
                OnCall::recipients()->reject(fn (User $u) => $u->id === $reporter->id),
                new PriorityWorkOrderCreatedNotification($workOrder->load('room', 'equipment', 'reporter'))
            );
        }

        return $workOrder;
    }
}
