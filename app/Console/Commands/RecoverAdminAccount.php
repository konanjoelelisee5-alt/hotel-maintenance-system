<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Porte de secours hors application : si plus aucun administrateur ne peut se
 * connecter (comptes désactivés, mots de passe oubliés), la personne qui a accès
 * au serveur rétablit un compte admin sans passer par la base à la main.
 */
class RecoverAdminAccount extends Command
{
    /**
     * Le nom et la signature de la commande, tel qu'on l'appellera en ligne de commande.
     */
    protected $signature = 'admin:recover {email : E-mail du compte à rétablir comme administrateur}';

    /**
     * Description affichée dans "php artisan list".
     */
    protected $description = 'Réactive un compte, lui donne le rôle administrateur et lui attribue un mot de passe temporaire.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("Aucun compte n'utilise l'adresse {$this->argument('email')}.");

            return self::FAILURE;
        }

        $this->line("Compte : {$user->name} — {$user->role_label}, ".($user->is_active ? 'actif' : 'désactivé'));

        if (! $this->confirm('Rétablir ce compte comme administrateur avec un nouveau mot de passe temporaire ?')) {
            $this->info('Opération annulée.');

            return self::SUCCESS;
        }

        $temporaryPassword = Str::password(12, symbols: false);

        $user->forceFill([
            'role' => UserRole::Admin,
            'is_active' => true,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        ActivityLog::record('user.admin_recovered', "Compte {$user->name} rétabli comme administrateur (commande de secours)", $user);

        $this->info("Compte rétabli. Mot de passe temporaire : {$temporaryPassword}");
        $this->line('Il devra être changé à la première connexion.');

        return self::SUCCESS;
    }
}
