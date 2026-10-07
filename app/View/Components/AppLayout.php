<?php

namespace App\View\Components;

use App\Enums\UserRole;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Layout applicatif — sidebar/nav de la refonte visuelle, pour toute l'app.
 *
 * Deux façons de définir l'en-tête, pour permettre une migration progressive :
 * - nouvelles pages : props `page-title` / `crumb` ;
 * - pages pas encore reprises dans le nouveau design : `<x-slot name="header">`
 *   (compatibilité Breeze historique), affiché dans le même bandeau d'en-tête.
 */
class AppLayout extends Component
{
    public function __construct(
        public string $pageTitle = '',
        public string $crumb = '',
        public ?string $backRoute = null,
        // Parcours sans distraction (signalement) : navigation masquée sur téléphone et tablette.
        public bool $focus = false,
    ) {}

    public function render(): View
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest('created_at')->limit(6)->get();

        // Une seule coquille pour tous les rôles : la « feuille » claire (layouts.sheet) ; chaque
        // rôle y a ses menus (Navigation) et ses particularités (poste partagé, envois hors réseau).
        return view('layouts.sheet', [
            'nav' => Navigation::forSidebar($user),
            'bottomNav' => Navigation::forBottomNav($user),
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
