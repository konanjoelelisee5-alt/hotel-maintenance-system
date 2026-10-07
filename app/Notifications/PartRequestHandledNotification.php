<?php

namespace App\Notifications;

use App\Models\PartRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient le technicien que sa demande de pièce est traitée (avec le mot du manager).
 */
class PartRequestHandledNotification extends Notification
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
            'message' => "Pièce « {$this->partRequest->description} » ({$workOrder->code()}) : demande traitée"
                .($this->partRequest->handling_note ? ' — '.$this->partRequest->handling_note : '').'.',
        ];
    }
}
