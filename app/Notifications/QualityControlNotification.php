<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QualityControlNotification extends Notification
{
    use Queueable;

    protected WorkOrder $workOrder;
    protected string $decision;
    protected ?string $comment;

    public function __construct(WorkOrder $workOrder, string $decision, ?string $comment = null)
    {
        $this->workOrder = $workOrder;
        $this->decision = $decision;
        $this->comment = $comment;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->decision === 'approuve'
            ? "L'OT « {$this->workOrder->title} » a été validé et clôturé."
            : "L'OT « {$this->workOrder->title} » a été rejeté" . ($this->comment ? " : {$this->comment}" : '. Une correction est requise.');

        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'decision' => $this->decision,
            'message' => $message,
        ];
    }
}