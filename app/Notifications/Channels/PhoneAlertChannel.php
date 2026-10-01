<?php

namespace App\Notifications\Channels;

use App\Contracts\PhoneAlertSender;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Canal de notification Laravel : une notification qui le déclare dans via()
 * doit fournir toPhoneAlert(), le texte court envoyé au téléphone.
 */
class PhoneAlertChannel
{
    public function __construct(private PhoneAlertSender $sender)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('phoneAlert', $notification);

        if (blank($phone)) {
            // L'alerte reste visible dans la cloche ; on trace le trou pour l'admin.
            Log::warning("Alerte téléphone non envoyée : aucun numéro pour l'utilisateur #{$notifiable->getKey()}.");

            return;
        }

        $this->sender->send($phone, $notification->toPhoneAlert($notifiable));
    }
}
