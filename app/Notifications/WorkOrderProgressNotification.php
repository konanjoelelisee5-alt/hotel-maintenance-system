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
    public const CANCELLED = 'cancelled';

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
            self::ASSIGNED => "{$place} : {$technician} va s'en occuper"
                .($this->workOrder->scheduled_at ? ', passage prévu le '.$this->workOrder->scheduled_at->format('d/m à H\hi') : '')
                .'.',
            // Réclamation d'un client (type « Demande client ») : la réception doit le rappeler.
            self::RESOLVED => "{$place} : réparé par {$technician}. "
                .($this->workOrder->loadMissing('type')->type?->code === 'demande_client' ? 'Prévenez le client, puis confirmez.' : 'Vous pouvez vérifier.'),
            self::CANCELLED => $this->cancelledMessage($place),
        };

        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'step' => $this->step,
            'message' => $message,
        ];
    }

    /** Le motif est noté dans l'historique par l'annulation (« Annulé : <motif> »). */
    private function cancelledMessage(string $place): string
    {
        $history = $this->workOrder->statusHistories()->where('new_status', 'annule')->with('changedBy')->latest('id')->first();
        $reason = $history?->note ? trim(preg_replace('/^Annulé\s*:\s*/u', '', $history->note)) : null;

        return "{$place} : signalement annulé"
            .($history?->changedBy ? " par {$history->changedBy->name}" : '')
            .($reason ? ". Motif : {$reason}" : '.');
    }
}
