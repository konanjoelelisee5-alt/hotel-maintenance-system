<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Channels\PhoneAlertChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Ce que fait le Housekeeping sur un signalement et que d'autres doivent savoir :
 * - WITHDRAWN : l'agent retire son signalement → ceux qui avaient été alertés
 *   (astreinte, sur le téléphone aussi ; réception si un client était concerné) ;
 * - COMPLEMENTED : une précision est ajoutée → le technicien affecté, ou l'astreinte ;
 * - TEAM_URGENT : un agent signale une urgence → la gouvernante.
 */
class HousekeepingReportNotification extends Notification
{
    use Queueable;

    public const WITHDRAWN = 'withdrawn';

    public const COMPLEMENTED = 'complemented';

    public const TEAM_URGENT = 'team_urgent';

    public function __construct(
        protected WorkOrder $workOrder,
        protected string $kind,
        protected ?string $detail = null,
        protected bool $byPhone = false,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->byPhone ? ['database', PhoneAlertChannel::class] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'title' => $this->workOrder->title,
            'step' => $this->kind,
            'message' => $this->message(),
        ];
    }

    /** Texte court, sans accents typographiques (compatible SMS). */
    public function toPhoneAlert(object $notifiable): string
    {
        return "Hotel President - {$this->workOrder->code()} {$this->place()} : signalement RETIRE par {$this->reporter()}, ne pas se deplacer.";
    }

    private function message(): string
    {
        $who = $this->reporter();

        return match ($this->kind) {
            self::WITHDRAWN => "{$this->place()} : signalement {$this->workOrder->code()} retiré par {$who}"
                .($this->detail ? " ({$this->detail})" : '').'. Aucune intervention à prévoir.',
            self::COMPLEMENTED => "{$this->place()} : {$who} a ajouté une précision au signalement {$this->workOrder->code()}"
                .($this->detail ? " : « {$this->detail} »" : '.'),
            self::TEAM_URGENT => "Urgence signalée par {$who} : {$this->place()}, {$this->workOrder->title}"
                .($this->detail ? " ({$this->detail})" : '').'.',
        };
    }

    private function place(): string
    {
        return $this->workOrder->room?->label ?? 'Parties communes';
    }

    private function reporter(): string
    {
        return $this->workOrder->reporter?->name ?? 'le demandeur';
    }
}
