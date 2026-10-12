<?php

namespace App\Support;

use App\Jobs\SendBellPush;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Notifications push : « la cloche dans la poche ».
 *
 *  - chaque notification posée dans la cloche part aussi en push vers les appareils
 *    abonnés de la personne (App\Jobs\SendBellPush, en file d'attente) ;
 *  - à la déconnexion, l'appareil utilisé est désabonné : sur un poste partagé, le
 *    suivant ne reçoit pas les notifications du précédent.
 */
class PushNotifications
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            NotificationSent::class => 'mirrorBell',
            Logout::class => 'forgetDevice',
        ];
    }

    public function mirrorBell(NotificationSent $event): void
    {
        if ($event->channel !== 'database'
            || ! $event->response instanceof DatabaseNotification
            || ! $event->notifiable instanceof User
            || ! $event->notifiable->pushSubscriptions()->exists()) {
            return;
        }

        SendBellPush::dispatch($event->notifiable, $event->response->id)->afterCommit();
    }

    public function forgetDevice(Logout $event): void
    {
        $endpoint = session()->pull('push_endpoint');

        if ($endpoint && $event->user instanceof User) {
            $event->user->deletePushSubscription($endpoint);
        }
    }
}
