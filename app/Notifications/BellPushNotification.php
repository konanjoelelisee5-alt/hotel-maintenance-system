<?php

namespace App\Notifications;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Copie « push » d'une notification de la cloche, pour les appareils abonnés
 * (envoyée par App\Jobs\SendBellPush). Le clic ouvre la même page que dans la cloche.
 */
class BellPushNotification extends Notification
{
    public function __construct(protected DatabaseNotification $bell)
    {
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $data = $this->bell->data;

        return (new WebPushMessage)
            ->title(config('app.name'))
            ->body($data['message'] ?? $data['title'] ?? 'Nouvelle notification')
            ->icon(asset('images/pwa/icon-192.png'))
            ->badge(asset('images/pwa/favicon-32.png'))
            ->tag($this->bell->id)
            ->data(['url' => route('notifications.open', $this->bell->id)])
            // Une alerte de la nuit n'a plus de sens le lendemain ; urgence haute pour
            // réveiller le téléphone en veille.
            ->options(['TTL' => 12 * 3600, 'urgency' => 'high']);
    }
}
