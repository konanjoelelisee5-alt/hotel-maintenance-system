<?php

namespace App\Notifications;

use App\Models\Part;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    protected Part $part;

    public function __construct(Part $part)
    {
        $this->part = $part;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'part_id' => $this->part->id,
            'message' => "Stock bas : « {$this->part->name} » ({$this->part->quantity_on_hand} {$this->part->unit} restant, seuil : {$this->part->reorder_threshold}).",
        ];
    }
}