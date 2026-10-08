<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité posés sur chaque réponse :
 * - pas d'affichage de l'application dans le cadre d'un autre site (clic piégé) ;
 * - formulaires envoyés seulement vers l'application, pas de <base> ni de plugin ;
 * - pas de devinette du type des fichiers servis ;
 * - caméra et micro réservés à l'application (signalement vocal, photo, QR), le reste coupé ;
 * - en HTTPS, le navigateur retient de ne plus jamais passer en HTTP (HSTS).
 * Pas de liste stricte de scripts (Alpine et les scripts des pages en ont besoin en ligne) :
 * le texte saisi par les utilisateurs est échappé à l'affichage.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()',
            'Content-Security-Policy' => "frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'",
        ];
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
