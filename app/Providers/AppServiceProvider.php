<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Politique de mot de passe renforcée en production (comptes admin/manager/technicien
        // ayant accès aux données de l'hôtel) ; on reste sur le minimum Laravel en local/tests
        // pour ne pas gêner le développement.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->mixedCase()->numbers()->symbols()
            : Password::min(8)
        );
    }
}
