<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page « Nouveautés » (journal des versions, filtré par rôle) et version en service,
 * interrogée par les pages restées ouvertes pour proposer d'actualiser.
 */
class ReleaseNotesController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $releases = AppVersion::releasesFor($user);
        // Ce qu'il n'avait pas encore lu : mis en avant, puis le point bleu s'éteint.
        $seen = $user->seen_version;

        if ($seen !== AppVersion::current()) {
            $user->forceFill(['seen_version' => AppVersion::current()])->save();
        }

        return view('release-notes.index', compact('releases', 'seen'));
    }

    public function version(): JsonResponse
    {
        return response()->json(['version' => AppVersion::current()])
            ->header('Cache-Control', 'no-store');
    }
}
