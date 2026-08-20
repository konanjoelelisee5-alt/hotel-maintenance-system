<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PreventiveWorkOrderGeneratedNotification extends Notification
{
    use Queueable;

    protected WorkOrder $workOrder;

    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
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
            'due_date' => $this->workOrder->due_date?->toIso8601String(),
            'message' => "Nouvelle maintenance préventive assignée automatiquement : « {$this->workOrder->title} »"
                . ($this->workOrder->due_date ? ' pour le ' . $this->workOrder->due_date->format('d/m/Y') : ''),
        ];
    }
}
