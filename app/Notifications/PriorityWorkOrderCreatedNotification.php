<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Channels\PhoneAlertChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte d'astreinte : un OT urgent ou haut vient d'être signalé. Envoyée dans la
 * cloche ET sur le téléphone, car la nuit personne n'a l'application ouverte.
 */
class PriorityWorkOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(protected WorkOrder $workOrder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', PhoneAlertChannel::class];
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
