<?php

namespace App\Notifications;

use App\Models\PartRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient le manager qu'un technicien attend une pièce absente du magasin.
 */
class PartRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(protected PartRequest $partRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $workOrder = $this->partRequest->workOrder;

        return [
            'work_order_id' => $workOrder->id,
            'title' => $workOrder->title,
            'message' => ($this->partRequest->requester?->name ?? 'Un technicien')." demande {$this->partRequest->quantity} × « {$this->partRequest->description} »"
                ." pour {$workOrder->code()}".($workOrder->room ? " ({$workOrder->room->label})" : '').' : pièce absente du magasin.',
        ];
    }
}
