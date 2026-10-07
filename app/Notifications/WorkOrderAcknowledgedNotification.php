<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient l'astreinte qu'un technicien a pris en charge une urgence.
 */
class WorkOrderAcknowledgedNotification extends Notification
{
    use Queueable;

    public function __construct(protected WorkOrder $workOrder, protected string $technician)
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
            'title' => $this->workOrder->title,
            'message' => "{$this->technician} s'occupe de l'urgence « {$this->workOrder->title} »"
                .($this->workOrder->room ? " ({$this->workOrder->room->label})" : '').'.',
        ];
    }
}
