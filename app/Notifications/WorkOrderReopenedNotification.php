<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Le service demandeur signale que la panne n'est pas réglée : l'OT est rouvert.
 * Prévenus : l'intervenant et les responsables qui reçoivent les alertes.
 */
class WorkOrderReopenedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected WorkOrder $workOrder,
        protected User $requester,
        protected string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $place = $this->workOrder->room?->label ?? 'Parties communes';

        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'step' => 'reopened',
            'message' => "↩ {$place} : toujours en panne selon {$this->requester->name}. Motif : {$this->reason}",
        ];
    }
}
