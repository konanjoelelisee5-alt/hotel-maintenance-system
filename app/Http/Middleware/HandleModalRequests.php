<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fenêtres (modales) ouvertes par resources/js/modal.js : la requête porte l'en-tête
 * X-Modal. Une redirection (ex. « OT créé » → fiche de l'OT, ou « confirmez votre mot
 * de passe ») est rendue en JSON { redirect } : le navigateur s'y rend lui-même, sans
 * que fetch ne la suive en silence — ce qui consommerait le message flash « succès ».
 */
class HandleModalRequests
{
    public const HEADER = 'X-Modal';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasHeader(self::HEADER) && $response instanceof RedirectResponse) {
            return new JsonResponse(['redirect' => $response->getTargetUrl()]);
        }

        return $response;
    }
}
