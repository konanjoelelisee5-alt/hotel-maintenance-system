<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * Alertes du dashboard administrateur.
 */
class SystemAlerts
{
    /**
     * Ce que seul l'administrateur peut corriger : comptes et réglages qui
     * empêcheraient l'application de prévenir les bonnes personnes ou exposent un accès.
     *
     * @return array<int, array{label: string, meta: string, color: string, url: string}>
     */
    public static function forAdmin(): array
    {
        $alerts = [];

        $withoutPhone = User::whereIn('role', [UserRole::Admin, UserRole::Manager])
            ->where('is_active', true)->where('receives_maintenance_alerts', true)->whereNull('phone')->count();
        if ($withoutPhone > 0) {
            $alerts[] = ['label' => "{$withoutPhone} responsable(s) d'astreinte sans téléphone", 'meta' => 'Les alertes de nuit ne peuvent pas partir', 'color' => 'red', 'url' => route('on-call.edit')];
        }

        $temporary = User::where('is_active', true)->where('must_change_password', true)->count();
        if ($temporary > 0) {
            $alerts[] = ['label' => "{$temporary} compte(s) avec mot de passe provisoire", 'meta' => 'Pas encore remplacé par leur titulaire', 'color' => 'amber', 'url' => route('users.index')];
        }

        $failed = ActivityLog::where('action', 'auth.failed')->where('created_at', '>=', now()->subDay())->count();
        $lockouts = ActivityLog::where('action', 'auth.lockout')->where('created_at', '>=', now()->subDay())->count();
        if ($failed >= 5 || $lockouts > 0) {
            $alerts[] = ['label' => "{$failed} échec(s) de connexion en 24 h".($lockouts ? ", {$lockouts} blocage(s)" : ''), 'meta' => 'Mot de passe oublié ou tentative d\'intrusion', 'color' => $lockouts ? 'red' : 'amber', 'url' => route('activity-logs.index', ['action' => 'auth.failed'])];
        }

        return $alerts;
    }
}
