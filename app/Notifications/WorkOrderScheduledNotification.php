<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WorkOrderScheduledNotification extends Notification
{
    use Queueable;

    protected WorkOrder $workOrder;
    protected bool $isReschedule;

    public function __construct(WorkOrder $workOrder, bool $isReschedule = false)
    {
        $this->workOrder = $workOrder;
        $this->isReschedule = $isReschedule;
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
            'scheduled_at' => $this->workOrder->scheduled_at?->toIso8601String(),
            'message' => $this->isReschedule
                ? "L'OT « {$this->workOrder->title} » a été replanifié pour le " . $this->workOrder->scheduled_at->format('d/m/Y à H:i')
                : "Un nouvel OT vous a été planifié : « {$this->workOrder->title} » le " . $this->workOrder->scheduled_at->format('d/m/Y à H:i'),
        ];
    }
}