<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        $oldEmail = $user->getOriginal('email');
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            ActivityLog::record(
                'user.identity_changed',
                "{$user->name} a changé son adresse e-mail : {$oldEmail} → {$user->email}",
                $user,
                ['email' => ['from' => $oldEmail, 'to' => $user->email], 'target_role' => $user->role->value],
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
