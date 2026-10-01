<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un mot de passe temporaire (réinitialisation par un admin) est connu de deux
 * personnes : tant que l'utilisateur ne l'a pas remplacé, seule la page profil
 * (pour le changer) et la déconnexion restent accessibles.
 */
class EnsurePasswordIsChanged
{
    private const ALLOWED_ROUTES = ['profile.edit', 'password.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Votre mot de passe a été réinitialisé par un administrateur. Choisissez-en un nouveau pour continuer.');
        }

        return $next($request);
    }
}
