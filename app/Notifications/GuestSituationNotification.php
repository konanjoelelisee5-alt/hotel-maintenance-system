<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * La réception a changé la situation du client d'une chambre en panne (relogé,
 * sorti, arrivée prévue…) : le technicien affecté le sait (accès, échéance).
 */
class GuestSituationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected WorkOrder $workOrder,
        protected string $text,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'message' => ($this->workOrder->room?->label ?? 'Chambre').' · '.$this->workOrder->code().' — '.$this->text,
        ];
    }
}
