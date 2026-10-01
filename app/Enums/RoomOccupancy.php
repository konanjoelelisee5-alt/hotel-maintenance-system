<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Occupation de la chambre au moment du signalement, déclarée d'un geste par
 * l'agent : l'application n'est pas reliée à Opera, c'est l'agent qui est devant la porte.
 */
enum RoomOccupancy: string
{
    case Libre = 'libre';
    case ClientAbsent = 'client_absent';
    case ClientPresent = 'client_present';
    case Depart = 'depart';

    /** Heure de retour supposée d'un client sorti : la réparation doit être faite avant. */
    public const GUEST_RETURN_TIME = '17:00';

    /** Délai minimal laissé à la maintenance quand le signalement arrive tard. */
    public const MIN_REPAIR_HOURS = 2;

    public function label(): string
    {
        return match ($this) {
            self::Libre => 'Chambre libre',
            self::ClientAbsent => 'Client sorti',
            self::ClientPresent => 'Client dans la chambre',
            self::Depart => 'Départ aujourd\'hui',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Libre => '🟢',
            self::ClientAbsent => '🧳',
            self::ClientPresent => '🛏️',
            self::Depart => '🚪',
        };
    }

    /** Une chambre vendue ce soir : on ne la bloque pas, on répare ou la réception déloge. */
    public function isOccupied(): bool
    {
        return $this === self::ClientAbsent || $this === self::ClientPresent;
    }

    /** Échéance de réparation imposée par le retour du client (null si pas de client à attendre). */
    public function repairDeadline(CarbonInterface $now): ?CarbonInterface
    {
        if ($this !== self::ClientAbsent) {
            return null;
        }

        $deadline = $now->copy()->setTimeFromTimeString(self::GUEST_RETURN_TIME);
        $earliest = $now->copy()->addHours(self::MIN_REPAIR_HOURS);

        return $deadline->lt($earliest) ? $earliest : $deadline;
    }
}
