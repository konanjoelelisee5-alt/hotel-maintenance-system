<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient un technicien qu'il reprend un OT d'un collègue parti.
 */
class WorkOrderReassignedNotification extends Notification
{
    use Queueable;

    public function __construct(protected WorkOrder $workOrder, protected string $previousAssignee)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'message' => "Vous reprenez l'OT « {$this->workOrder->title} »"
                .($this->workOrder->room ? " ({$this->workOrder->room->label})" : '')
                ." de {$this->previousAssignee}.",
        ];
    }
}
