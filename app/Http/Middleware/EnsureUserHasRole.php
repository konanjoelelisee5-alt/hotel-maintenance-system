<?php

namespace App\Http\Middleware;

use App\Support\OpenAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Accès ouvert (démonstration) : tout utilisateur connecté passe.
        if ($request->user() && OpenAccess::enabled()) {
            return $next($request);
        }

        if (! $request->user() || ! in_array($request->user()->role?->value, $roles, true)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}