<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
    // Adresse réelle du visiteur derrière un proxy (Render : TRUSTED_PROXIES=*). Sans proxy
    // (serveur dans l'hôtel), ne faire confiance à personne : sinon un en-tête
    // X-Forwarded-For inventé suffit à changer d'adresse et à contourner les limites d'essais.
    if ($proxies = env('TRUSTED_PROXIES')) {
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
    }
    $middleware->web(append: [
        \App\Http\Middleware\SecurityHeaders::class,
        \App\Http\Middleware\RejectArrayQueryParameters::class,
        \App\Http\Middleware\EnsureUserIsActive::class,
        \App\Http\Middleware\EnsurePasswordIsChanged::class,
        \App\Http\Middleware\HandleModalRequests::class,
    ]);
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // expectsJson : erreurs de saisie d'un formulaire de fenêtre (resources/js/modal.js)
        // rendues en 422 + champs en erreur, au lieu d'une redirection vers la page.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
