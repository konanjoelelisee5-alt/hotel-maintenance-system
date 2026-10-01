<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Support\OnCall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnCallController extends Controller
{
    public function edit(): View
    {
        return view('on-call.edit', [
            'dayStart' => OnCall::dayStart(),
            'dayEnd' => OnCall::dayEnd(),
            'isDayTime' => OnCall::isDayTime(),
            'current' => OnCall::recipients(),
            'dayTeam' => User::maintenanceAlertRecipients(UserRole::Manager)->orderBy('name')->get(),
            'nightTeam' => User::maintenanceAlertRecipients(UserRole::Admin)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_start' => ['required', 'date_format:H:i'],
            // La journée ne passe pas minuit : le début doit précéder la fin.
            'day_end' => ['required', 'date_format:H:i', 'after:day_start'],
        ], [
            'day_end.after' => "L'heure de fin de journée doit être après l'heure de début.",
        ]);

        Setting::put(OnCall::DAY_START_KEY, $validated['day_start']);
        Setting::put(OnCall::DAY_END_KEY, $validated['day_end']);

        return redirect()->route('on-call.edit')->with('success', "Horaires d'astreinte enregistrés.");
    }
}
