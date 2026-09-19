<?php

namespace App\View\Components;

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
    ) {}

    public function render(): View
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest('created_at')->limit(6)->get();

        return view('layouts.app', [
            'nav' => Navigation::forSidebar($user),
            'bottomNav' => Navigation::forBottomNav($user),
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
