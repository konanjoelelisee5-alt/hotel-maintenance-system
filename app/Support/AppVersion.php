<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Version de l'application et page « Nouveautés », lues dans resources/changelog.php :
 * la version courante est la première entrée du journal (une seule source à tenir).
 *
 * Affichée en bas du menu (x-app-version), sur la page de connexion et dans
 * <meta name="app-version"> (comparée par x-update-available à GET /version).
 */
class AppVersion
{
    public const TYPES = [
        'nouveau' => ['Nouveau', 'blue'],
        'ameliore' => ['Amélioré', 'green'],
        'corrige' => ['Corrigé', 'grey'],
    ];

    /** @var array<int, array{version: string, date: string, title: ?string, changes: array}>|null */
    private static ?array $releases = null;

    /** @return array<int, array{version: string, date: string, title: ?string, changes: array}> */
    public static function releases(): array
    {
        return self::$releases ??= require resource_path('changelog.php');
    }

    public static function current(): string
    {
        return self::releases()[0]['version'];
    }

    /** Version de test (0.9.0-beta.2, 1.0.0-rc.1) : badge « Test ». */
    public static function isPreRelease(?string $version = null): bool
    {
        return str_contains($version ?? self::current(), '-');
    }

    public static function date(array $release): Carbon
    {
        return Carbon::parse($release['date']);
    }

    /**
     * Versions et changements qui concernent ce rôle, de la plus récente à la plus ancienne.
     *
     * @return array<int, array{version: string, date: string, title: ?string, changes: array}>
     */
    public static function releasesFor(User $user): array
    {
        $role = $user->role?->value;

        return array_values(array_filter(array_map(function (array $release) use ($role) {
            $release['changes'] = array_values(array_filter(
                $release['changes'],
                fn (array $change) => $change['roles'] === null || in_array($role, $change['roles'], true),
            ));

            return $release;
        }, self::releases()), fn (array $release) => $release['changes'] !== []));
    }

    /** Point bleu sur « Nouveautés » : une version qui le concerne n'a pas encore été lue. */
    public static function hasUnseen(User $user): bool
    {
        $latest = self::releasesFor($user)[0]['version'] ?? null;

        // version_compare lit les suffixes : 0.9.0-beta.1 < 0.9.0-beta.2 < 1.0.0-rc.1 < 1.0.0.
        return $latest !== null && version_compare($latest, $user->seen_version ?? '0', '>');
    }
}
