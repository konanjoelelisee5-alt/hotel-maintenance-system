<?php

namespace App\Notifications;

use App\Models\EscalationRule;
use App\Models\WorkOrder;
use App\Notifications\Channels\PhoneAlertChannel;
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
        // Un retard sur un OT urgent ou haut doit atteindre quelqu'un même la nuit :
        // c'est le filet de sécurité si l'alerte de création est restée sans suite.
        return $this->workOrder->priority?->triggersOnCallAlert()
            ? ['database', PhoneAlertChannel::class]
            : ['database'];
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

    /** Texte court, sans emoji ni guillemets typographiques (compatible SMS). */
    public function toPhoneAlert(object $notifiable): string
    {
        return "Hotel President - RETARD {$this->workOrder->code()} ({$this->rule->trigger_type_label}) : {$this->workOrder->title}. Merci de prendre en charge.";
    }
}
