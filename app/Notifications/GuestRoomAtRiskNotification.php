<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Un client risque de retrouver sa chambre en panne : la réception décide (excuses,
 * délogement dans Opera) et s'en occupe comme d'habitude.
 */
class GuestRoomAtRiskNotification extends Notification
{
    use Queueable;

    /** Client sorti, retour proche, réparation pas terminée. */
    public const DEADLINE = 'deadline';

    /** Panne urgente alors que le client est dans la chambre. */
    public const GUEST_INSIDE = 'guest_inside';

    public function __construct(
        protected WorkOrder $workOrder,
        protected string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $place = $this->workOrder->room?->label ?? 'Chambre';

        $message = match ($this->reason) {
            self::DEADLINE => "⚠️ {$place} : client attendu vers ".$this->workOrder->due_date?->format('H\hi')
                ." et la réparation n'est pas terminée ({$this->workOrder->title}). Prévoir un délogement ?",
            self::GUEST_INSIDE => "⚠️ {$place} : panne urgente avec le client dans la chambre ({$this->workOrder->title}).",
        };

        return [
            'target' => 'room-blocks',
            'work_order_id' => $this->workOrder->id,
            'reason' => $this->reason,
            'message' => $message,
        ];
    }
}
