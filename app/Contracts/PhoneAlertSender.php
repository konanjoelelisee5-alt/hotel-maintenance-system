<?php

namespace App\Contracts;

/**
 * Envoi d'une alerte sur le téléphone d'un utilisateur (SMS, WhatsApp, appel...).
 *
 * L'application ne dépend que de ce contrat : brancher un fournisseur réel
 * (Twilio, API SMS Orange...) revient à écrire une nouvelle implémentation et à
 * la sélectionner dans config/services.php, sans toucher au reste du code.
 */
interface PhoneAlertSender
{
    public function send(string $phone, string $message): void;
}
