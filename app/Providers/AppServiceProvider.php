<?php

namespace App\Providers;

use App\Contracts\PhoneAlertSender;
use App\Services\PhoneAlerts\LogPhoneAlertSender;
use App\Support\AuthenticationAudit;
use App\Support\OpenAccess;
use App\Support\SchedulerHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fournisseur des alertes téléphone (cf. App\Contracts\PhoneAlertSender).
        // Seul le mode "log" existe pour l'instant ; un fournisseur réel s'ajoutera ici.
        $this->app->bind(PhoneAlertSender::class, fn () => match (config('services.phone_alerts.driver')) {
            default => new LogPhoneAlertSender(),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Connexions, déconnexions, échecs et blocages au journal d'activité.
        Event::subscribe(AuthenticationAudit::class);

        // En production, les liens (dont ceux des courriels « mot de passe oublié ») partent
        // toujours de l'adresse officielle : un en-tête Host inventé ne doit pas pouvoir y
        // glisser le site d'un attaquant et lui faire recevoir le jeton de réinitialisation.
        if ($this->app->isProduction() && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Accès ouvert (démonstration) : on peut OUVRIR toutes les pages. Les autres droits
        // (qui répare, qui pilote…) restent ceux du rôle : ils construisent la fiche de
        // chaque métier ; pour agir comme un autre rôle, on passe par « Voir en tant que ».
        Gate::before(fn ($user, string $ability) => OpenAccess::enabled() && in_array($ability, OpenAccess::VIEW_ABILITIES, true) ? true : null);

        // État des tâches automatiques, affiché sur la supervision admin.
        View::composer('dashboards.admin', fn ($view) => $view->with('schedulerHealth', SchedulerHealth::report()));

        // Politique de mot de passe renforcée en production (comptes admin/manager/technicien
        // ayant accès aux données de l'hôtel) ; on reste sur le minimum Laravel en local/tests
        // pour ne pas gêner le développement.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->mixedCase()->numbers()->symbols()
            : Password::min(8)
        );
    }
}
