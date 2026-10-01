<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\ActivityLog;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,manager,technicien,housekeeping,reception'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        ActivityLog::record('user.created', "Création de l'utilisateur {$user->name} ({$user->role_label})", $user);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,manager,technicien,housekeeping,reception'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $losesAdmin = $validated['role'] !== UserRole::Admin->value || ! $validated['is_active'];

        // Le formulaire grise déjà ces champs pour son propre compte, mais seule
        // cette vérification serveur empêche une requête forgée de les modifier.
        if ($user->id === Auth::id() && ($validated['role'] !== $user->role->value || ! $validated['is_active'])) {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre propre compte.',
            ]);
        }

        if ($losesAdmin && $user->isLastActiveAdmin()) {
            throw ValidationException::withMessages([
                'role' => "C'est le dernier administrateur actif : nommez-en un autre avant de le rétrograder ou de le désactiver.",
            ]);
        }

        $oldRole = $user->role;
        $wasActive = $user->is_active;

        $user->update($validated);

        if ($oldRole !== $user->role) {
            ActivityLog::record(
                'user.role_changed',
                "Changement de rôle de {$user->name} : {$oldRole->label()} → {$user->role_label}",
                $user,
                ['from' => $oldRole->value, 'to' => $user->role->value],
            );
        }

        if ($wasActive !== $user->is_active) {
            ActivityLog::record(
                $user->is_active ? 'user.reactivated' : 'user.deactivated',
                ($user->is_active ? 'Réactivation' : 'Désactivation')." de l'utilisateur {$user->name}",
                $user,
            );
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Sécurité : on empêche un admin de se désactiver lui-même par erreur
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('warning', "C'est le dernier administrateur actif : il ne peut pas être désactivé.");
        }

        $user->update(['is_active' => false]);
        ActivityLog::record('user.deactivated', "Désactivation de l'utilisateur {$user->name}", $user);

        return back()->with('success', 'Utilisateur désactivé.');
    }

    /**
     * Attribue un mot de passe temporaire, affiché une seule fois à l'admin qui le
     * transmet à l'employé (beaucoup n'ont pas d'e-mail fiable pour le lien Breeze).
     * L'employé devra le remplacer à sa prochaine connexion.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'Pour votre propre compte, changez votre mot de passe depuis votre profil.');
        }

        $temporaryPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        ActivityLog::record('user.password_reset', "Réinitialisation du mot de passe de {$user->name}", $user);

        return redirect()->route('users.index')
            ->with('success', "Mot de passe de {$user->name} réinitialisé.")
            ->with('temporary_password', ['name' => $user->name, 'password' => $temporaryPassword]);
    }
}
