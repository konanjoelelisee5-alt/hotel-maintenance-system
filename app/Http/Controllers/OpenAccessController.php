<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\OpenAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * « Voir en tant que » (accès ouvert seulement) : se connecter comme un autre compte
 * pour voir l'application avec ses écrans. Introuvable (404) hors accès ouvert.
 */
class OpenAccessController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless(OpenAccess::enabled() && $user->is_active, 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->dashboardRoute());
    }
}
