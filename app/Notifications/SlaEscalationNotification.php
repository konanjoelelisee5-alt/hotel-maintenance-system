<?php

namespace App\Notifications;

use App\Models\EscalationRule;
use App\Models\WorkOrder;
use App\Notifications\Channels\PhoneAlertChannel;
use App\Notifications\Concerns\SendsMailInBackground;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsMailInBackground;

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
            ? ['database', PhoneAlertChannel::class, 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Retard {$this->workOrder->code()} — {$this->workOrder->title}")
            ->line("L'ordre de travail **{$this->workOrder->code()}** est en retard : {$this->rule->trigger_type_label}.")
            ->line("« {$this->workOrder->title} »")
            ->action('Prendre en charge', route('work-orders.show', $this->workOrder))
            ->line("Ce rappel part quand une urgence reste sans suite dans les délais prévus.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'rule' => $this->rule->trigger_type_label,
            'message' => "Délai SLA : {$this->rule->trigger_type_label} pour l'OT « {$this->workOrder->title} ».",
        ];
    }

    /** Texte court, sans emoji ni guillemets typographiques (compatible SMS). */
    public function toPhoneAlert(object $notifiable): string
    {
        return "Hotel President - RETARD {$this->workOrder->code()} ({$this->rule->trigger_type_label}) : {$this->workOrder->title}. Merci de prendre en charge.";
    }
}
