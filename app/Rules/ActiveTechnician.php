<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * L'utilisateur choisi doit être un technicien actif. Avant, "exists:users,id"
 * acceptait n'importe quel compte, y compris un technicien parti ou un réceptionniste.
 *
 * $allowedId : valeur déjà en place qu'on accepte même si elle ne remplit plus
 * la condition (modifier un vieil OT sans être forcé de changer son assigné).
 * $excludedId : compte à refuser (le technicien qui part ne peut pas se remplacer).
 */
class ActiveTechnician implements ValidationRule
{
    public function __construct(private ?int $allowedId = null, private ?int $excludedId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->excludedId !== null && (int) $value === $this->excludedId) {
            $fail("Le technicien qui part ne peut pas être son propre remplaçant.");

            return;
        }

        if ($this->allowedId !== null && (int) $value === $this->allowedId) {
            return;
        }

        $ok = User::whereKey($value)
            ->where('role', UserRole::Technicien)
            ->where('is_active', true)
            ->exists();

        if (! $ok) {
            $fail('Le technicien sélectionné est invalide ou désactivé.');
        }
    }
}
