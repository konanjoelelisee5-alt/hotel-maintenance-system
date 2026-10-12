<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Channels\PhoneAlertChannel;
use App\Notifications\Concerns\SendsMailInBackground;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte d'astreinte : un OT urgent ou haut vient d'être signalé. Envoyée dans la
 * cloche, sur le téléphone ET par e-mail, car la nuit personne n'a l'application ouverte.
 */
class PriorityWorkOrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsMailInBackground;

    public function __construct(protected WorkOrder $workOrder)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', PhoneAlertChannel::class, 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("OT {$this->priorityLabel()} {$this->workOrder->code()} — {$this->location()}")
            ->line("Un ordre de travail {$this->priorityLabel()} vient d'être signalé.")
            ->line("**{$this->location()}** : {$this->workOrder->title}")
            ->line("Signalé par {$this->reporterName()}.")
            ->action("Ouvrir l'ordre de travail", route('work-orders.show', $this->workOrder))
            ->line("Vous recevez cet e-mail car vous êtes d'astreinte.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'message' => "Nouvel OT {$this->priorityLabel()} — {$this->location()} : « {$this->workOrder->title} » (signalé par {$this->reporterName()}).",
        ];
    }

    /** Texte court, sans emoji ni guillemets typographiques (compatible SMS). */
    public function toPhoneAlert(object $notifiable): string
    {
        return "Hotel President - OT {$this->priorityLabel()} {$this->workOrder->code()} - {$this->location()} : {$this->workOrder->title}. Signale par {$this->reporterName()}.";
    }

    private function priorityLabel(): string
    {
        return mb_strtoupper($this->workOrder->priority?->label ?? 'prioritaire');
    }

    private function location(): string
    {
        return match (true) {
            (bool) $this->workOrder->room => $this->workOrder->room->label,
            (bool) $this->workOrder->equipment => $this->workOrder->equipment->name,
            default => 'Lieu non précisé',
        };
    }

    private function reporterName(): string
    {
        return $this->workOrder->reporter?->name ?? 'inconnu';
    }
}
