<?php

namespace App\Notifications;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Contrôle mutuel entre administrateurs : chacun est informé des actions sensibles
 * des autres (comptes, règles SLA/escalade). Personne n'est bloqué, tout le monde sait.
 */
class SensitiveAdminActionNotification extends Notification
{
    use Queueable;

    public function __construct(protected ActivityLog $log)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'activity_log_id' => $this->log->id,
            'message' => ($this->log->user?->name ?? 'Système').' : '.$this->log->description,
        ];
    }
}
