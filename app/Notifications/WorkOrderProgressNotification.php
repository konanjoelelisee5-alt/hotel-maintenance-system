<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avancement d'un signalement, pour le service qui l'a fait (l'agent et son
 * responsable) : « quelqu'un s'en occupe », puis « c'est réparé ».
 */
class WorkOrderProgressNotification extends Notification
{
    use Queueable;

    public const ASSIGNED = 'assigned';
    public const RESOLVED = 'resolved';

    public function __construct(
        protected WorkOrder $workOrder,
        protected string $step,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $place = $this->workOrder->room
            ? ($this->workOrder->room->label ?? 'Chambre '.$this->workOrder->room->number)
            : 'Parties communes';
        $technician = $this->workOrder->assignee?->name ?? 'un technicien';

        $message = match ($this->step) {
            self::ASSIGNED => "🔧 {$place} : {$technician} va s'en occuper"
                .($this->workOrder->scheduled_at ? ', passage prévu le '.$this->workOrder->scheduled_at->format('d/m à H\hi') : '')
                .'.',
            self::RESOLVED => "✅ {$place} : réparé par {$technician}. Vous pouvez vérifier.",
        };

        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'step' => $this->step,
            'message' => $message,
        ];
    }
}
