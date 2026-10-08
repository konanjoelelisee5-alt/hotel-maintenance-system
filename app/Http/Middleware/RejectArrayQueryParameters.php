<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les adresses des pages (filtres, recherche, onglets, tri) n'attendent que des valeurs
 * simples. Une liste glissée à la main (?search[]=x) faisait planter les listes en erreur
 * 500 : elle est remplacée par une valeur vide avant d'atteindre l'application.
 * Les formulaires envoyés (POST) ne sont pas concernés : leurs champs sont validés.
 */
class RejectArrayQueryParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query->all();
        $flat = array_map(fn ($value) => is_array($value) ? '' : $value, $query);

        if ($flat !== $query) {
            $request->query->replace($flat);
        }

        return $next($request);
    }
}
