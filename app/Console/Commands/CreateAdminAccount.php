<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Premier administrateur d'une vraie installation : la base de production part
 * vide (pas de données de démo), il faut bien un premier compte pour créer les autres.
 */
class CreateAdminAccount extends Command
{
    protected $signature = 'admin:create
        {email : E-mail du nouvel administrateur}
        {name : Nom complet}';

    protected $description = 'Crée un compte administrateur avec un mot de passe temporaire à changer à la première connexion.';

    public function handle(): int
    {
        $validator = Validator::make(
            ['email' => $this->argument('email'), 'name' => $this->argument('name')],
            ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'name' => ['required', 'string', 'max:255']],
            ['email.unique' => 'Ce compte existe déjà : utilisez php artisan admin:recover pour le rétablir.'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $temporaryPassword = Str::password(12, symbols: false);

        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'role' => UserRole::Admin,
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        ActivityLog::record('user.created', "Création de l'administrateur {$user->name} (commande serveur)", $user, ['role' => UserRole::Admin->value]);

        $this->info("Administrateur créé. Mot de passe temporaire : {$temporaryPassword}");
        $this->line('Il devra être changé à la première connexion.');

        return self::SUCCESS;
    }
}
