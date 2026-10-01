<?php

namespace App\Services\PhoneAlerts;

use App\Contracts\PhoneAlertSender;
use Illuminate\Support\Facades\Log;

/**
 * Mode démonstration : aucune alerte n'est réellement envoyée (pas de coût, pas
 * de fournisseur), le message est écrit dans le journal de l'application pour
 * montrer exactement ce qui partirait.
 */
class LogPhoneAlertSender implements PhoneAlertSender
{
    public function send(string $phone, string $message): void
    {
        Log::info("[ALERTE TÉLÉPHONE] → {$phone} : {$message}");
    }
}
