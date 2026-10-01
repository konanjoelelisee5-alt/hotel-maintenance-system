<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Astreinte : qui prévenir d'un problème de maintenance à un instant donné.
 *
 *  - en journée : les managers qui reçoivent les alertes de maintenance ;
 *  - la nuit    : les admins qui les reçoivent (le chef de maintenance, pas
 *                 le responsable informatique).
 *
 * Si l'équipe de garde est vide (personne de configuré, tous désactivés),
 * l'alerte part à l'autre équipe plutôt que dans le vide.
 */
class OnCall
{
    public const DAY_START_KEY = 'on_call.day_start';
    public const DAY_END_KEY = 'on_call.day_end';

    public const DEFAULT_DAY_START = '07:00';
    public const DEFAULT_DAY_END = '19:00';

    public static function dayStart(): string
    {
        return Setting::get(self::DAY_START_KEY, self::DEFAULT_DAY_START);
    }

    public static function dayEnd(): string
    {
        return Setting::get(self::DAY_END_KEY, self::DEFAULT_DAY_END);
    }

    public static function isDayTime(?CarbonInterface $at = null): bool
    {
        $time = ($at ?? now())->format('H:i');

        return $time >= self::dayStart() && $time < self::dayEnd();
    }

    /** Rôle de l'équipe de garde à cet instant. */
    public static function role(?CarbonInterface $at = null): UserRole
    {
        return self::isDayTime($at) ? UserRole::Manager : UserRole::Admin;
    }

    /**
     * @return Collection<int, User>
     */
    public static function recipients(?CarbonInterface $at = null): Collection
    {
        $role = self::role($at);
        $team = User::maintenanceAlertRecipients($role)->get();

        if ($team->isNotEmpty()) {
            return $team;
        }

        $fallback = $role === UserRole::Manager ? UserRole::Admin : UserRole::Manager;

        return User::maintenanceAlertRecipients($fallback)->get();
    }
}
