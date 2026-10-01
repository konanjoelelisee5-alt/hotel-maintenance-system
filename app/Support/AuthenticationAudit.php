<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Connexions au journal : qui est entré, quand, depuis quelle adresse, et les
 * tentatives ratées (un mot de passe essayé en boucle sur un compte admin se voit).
 * Enregistré par AppServiceProvider (pas dans app/Listeners, pour éviter la
 * découverte automatique qui l'inscrirait une seconde fois).
 */
class AuthenticationAudit
{
    public function onLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            ActivityLog::record('auth.login', "Connexion de {$event->user->name}", $event->user, [], $event->user);
        }
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            ActivityLog::record('auth.logout', "Déconnexion de {$event->user->name}", $event->user, [], $event->user);
        }
    }

    /** Pas d'auteur : la personne qui essaie n'est, par définition, pas identifiée. */
    public function onFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');

        ActivityLog::record(
            'auth.failed',
            $event->user instanceof User
                ? "Échec de connexion sur le compte de {$event->user->name} (mot de passe erroné)"
                : "Échec de connexion avec une adresse inconnue : {$email}",
            $event->user instanceof User ? $event->user : null,
            ['email' => $email],
        );
    }

    public function onLockout(Lockout $event): void
    {
        $email = (string) $event->request->input('email');

        ActivityLog::record(
            'auth.lockout',
            "Connexion bloquée temporairement après 5 échecs : {$email}",
            User::where('email', $email)->first(),
            ['email' => $email],
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            Failed::class => 'onFailed',
            Lockout::class => 'onLockout',
        ];
    }
}
