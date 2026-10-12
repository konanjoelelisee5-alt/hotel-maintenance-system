<?php

namespace App\Notifications;

use App\Models\ActivityLog;
use App\Notifications\Concerns\SendsMailInBackground;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Contrôle mutuel entre administrateurs : chacun est informé des actions sensibles
 * des autres (comptes, règles SLA/escalade). Personne n'est bloqué, tout le monde sait.
 * Aussi par e-mail : une trace hors de l'application, qu'un admin ne peut pas effacer.
 */
class SensitiveAdminActionNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsMailInBackground;

    public function __construct(protected ActivityLog $log)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $author = $this->log->user?->name ?? 'Système';

        return (new MailMessage)
            ->subject("Action d'administration : {$author}")
            ->line("**{$author}** vient de faire une action sensible :")
            ->line($this->log->description)
            ->line('Le '.$this->log->created_at->translatedFormat('j F Y à H:i').'.')
            ->action("Voir le journal d'activité", route('activity-logs.index'))
            ->line("Si cette action vous surprend, parlez-en à l'autre administrateur.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'activity_log_id' => $this->log->id,
            'message' => ($this->log->user?->name ?? 'Système').' : '.$this->log->description,
        ];
    }
}
