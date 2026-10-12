<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Notifications push : l'appareil (téléphone, ordinateur) s'abonne pour la personne
 * connectée (resources/js/push.js). Un même appareil n'appartient qu'à une personne :
 * sur un poste partagé, il passe à celle qui se connecte, et la déconnexion le désabonne
 * (App\Listeners\ForgetPushSubscriptionOnLogout, grâce à l'adresse gardée en session).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm',
        );
        $request->session()->put('push_endpoint', $data['endpoint']);

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2000']]);

        $request->user()->deletePushSubscription($data['endpoint']);
        $request->session()->forget('push_endpoint');

        return response()->noContent();
    }
}
