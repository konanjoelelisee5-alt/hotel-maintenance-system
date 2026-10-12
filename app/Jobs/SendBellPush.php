<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BellPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envoie en arrière-plan le push d'une notification de la cloche (la page n'attend pas
 * Google ou Apple). Déjà lue entre-temps dans l'application : rien n'est envoyé.
 */
class SendBellPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public User $user, public string $notificationId)
    {
    }

    public function handle(): void
    {
        $bell = $this->user->notifications()->find($this->notificationId);

        if ($bell && $bell->read_at === null && $this->user->is_active) {
            $this->user->notify(new BellPushNotification($bell));
        }
    }
}
