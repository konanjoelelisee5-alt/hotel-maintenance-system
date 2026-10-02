<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

/**
 * Accueil de l'espace « Paramètres » (admin) : les écrans de configuration, regroupés
 * par thème, sortis de la sidebar pour qu'elle ne montre que le travail quotidien.
 */
class SettingsController extends Controller
{
    public function __invoke(): View
    {
        return view('settings.index', ['groups' => Navigation::settings()]);
    }
}
