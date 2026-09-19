<?php

namespace App\Enums;

/**
 * Enveloppe la colonne `users.role` (string) existante — aucune migration.
 * Les valeurs ci-dessous doivent rester identiques à celles stockées en base.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Technicien = 'technicien';
    case Housekeeping = 'housekeeping';
    case Reception = 'reception';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Manager => 'Manager',
            self::Technicien => 'Technicien',
            self::Housekeeping => 'Housekeeping',
            self::Reception => 'Réception',
        };
    }

    /**
     * Nom de la route du dashboard correspondant au rôle.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Manager => 'manager.dashboard',
            self::Technicien => 'technicien.dashboard',
            self::Housekeeping => 'housekeeping.dashboard',
            self::Reception => 'reception.dashboard',
        };
    }

    /** Rôles qui voient tous les ordres de travail plutôt qu'un sous-ensemble limité. */
    public function seesAllWorkOrders(): bool
    {
        return $this === self::Admin || $this === self::Manager;
    }
}
