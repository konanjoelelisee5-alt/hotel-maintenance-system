<?php

namespace App\Notifications;

use App\Models\EscalationRule;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SlaEscalationNotification extends Notification
{
    use Queueable;

    protected WorkOrder $workOrder;
    protected EscalationRule $rule;

    public function __construct(WorkOrder $workOrder, EscalationRule $rule)
    {
        $this->workOrder = $workOrder;
        $this->rule = $rule;
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
            'rule' => $this->rule->trigger_type_label,
            'message' => "⚠ SLA — {$this->rule->trigger_type_label} pour l'OT « {$this->workOrder->title} ».",
        ];
    }
}