<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        ActivityLog::record('user.created', "Création de l'utilisateur {$validated['name']} ({$validated['role']})", $user ?? null);

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

        $user->update($validated);
        

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Sécurité : on empêche un admin de se désactiver lui-même par erreur
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['is_active' => false]);
        ActivityLog::record('user.deactivated', "Désactivation de l'utilisateur {$user->name}", $user);

        return back()->with('success', 'Utilisateur désactivé.');
    }
}