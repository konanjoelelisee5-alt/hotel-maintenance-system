<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\PhoneAlertChannel;

/**
 * Notification qui part aussi par e-mail (à utiliser avec ShouldQueue).
 *
 * La cloche et l'alerte téléphone restent immédiates (connexion « sync ») ; seul
 * l'e-mail passe par la file d'attente : la page n'attend pas le serveur d'envoi,
 * et une panne de Gmail ne fait pas échouer l'action de l'utilisateur. Il faut donc
 * un `php artisan queue:work` qui tourne (voir MISE-EN-SERVICE.md).
 */
trait SendsMailInBackground
{
    /** Nouvelles tentatives si le serveur d'envoi ne répond pas. */
    public int $tries = 3;

    /** @return array<int, int> secondes entre deux tentatives */
    public function backoff(): array
    {
        return [60, 300];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            PhoneAlertChannel::class => 'sync',
            'mail' => config('queue.default'),
        ];
    }
}
