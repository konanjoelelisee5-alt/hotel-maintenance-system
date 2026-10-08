<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Accès ouvert (APP_OPEN_ACCESS=true) : pour parcourir toutes les interfaces pendant
 * la mise au point. Le middleware de rôle, les contrôles de rôle des contrôleurs et les
 * policies de consultation (VIEW_ABILITIES) laissent tout passer, et un sélecteur permet
 * de se connecter en un clic comme un compte de chaque rôle.
 * En production, seulement sur un site de démonstration (SEED_DEMO_DATA=true, ex. Render) :
 * le vrai site de l'hôtel ne l'a jamais, même si APP_OPEN_ACCESS y restait activé.
 */
class OpenAccess
{
    /** Droits de policy levés en accès ouvert : consulter seulement. */
    public const VIEW_ABILITIES = ['view', 'viewAny'];

    public static function enabled(): bool
    {
        return (bool) config('app.open_access') && (! app()->isProduction() || (bool) config('app.demo'));
    }

    /**
     * Un compte actif par profil d'écran : chaque rôle, et pour housekeeping / réception
     * le responsable et l'agent (leurs écrans diffèrent).
     *
     * @return Collection<int, array{label: string, user: User}>
     */
    public static function profiles(): Collection
    {
        $users = User::where('is_active', true)->orderBy('id')->get();

        return collect(UserRole::cases())->flatMap(function (UserRole $role) use ($users) {
            $ofRole = $users->where('role', $role);

            if (! $role->hasDepartmentHead()) {
                return [['label' => $role->label(), 'user' => $ofRole->first()]];
            }

            return [
                ['label' => "Responsable {$role->label()}", 'user' => $ofRole->first(fn (User $u) => $u->isDepartmentHead())],
                ['label' => "Agent {$role->label()}", 'user' => $ofRole->first(fn (User $u) => ! $u->isDepartmentHead())],
            ];
        })->filter(fn (array $profile) => $profile['user'] !== null)->values();
    }
}
