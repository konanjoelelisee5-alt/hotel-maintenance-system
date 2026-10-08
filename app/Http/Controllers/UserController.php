<?php

namespace App\Http\Controllers;

use App\Actions\DeactivateUser;
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
        // Onglets : par rôle, responsables de service, comptes désactivés. Chaque onglet a
        // son compteur, calculé avec la même règle que la liste.
        $tabs = [
            '' => 'Tous', 'admin' => 'Administrateurs', 'manager' => 'Managers', 'technicien' => 'Techniciens',
            'housekeeping' => 'Housekeeping', 'reception' => 'Réception',
            'department_head' => 'Responsables', 'inactive' => 'Désactivés',
        ];
        $role = array_key_exists((string) $request->role, $tabs) ? (string) $request->role : '';
        $applyTab = fn ($q, string $key) => match ($key) {
            '' => $q,
            'department_head' => $q->where('is_department_head', true),
            'inactive' => $q->where('is_active', false),
            default => $q->where('role', $key),
        };

        $users = $applyTab(User::query(), $role)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $counts = collect($tabs)->map(fn ($label, $key) => $applyTab(User::query(), $key)->count());

        return view('users.index', compact('users', 'tabs', 'role', 'counts'));
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
            'is_department_head' => ['nullable', 'boolean'],
            ...$this->alertRules($request),
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $this->normalizePhone($validated['phone'] ?? null),
            'role' => $validated['role'],
            'is_department_head' => $this->departmentHeadFlag($request, $validated['role']),
            'receives_maintenance_alerts' => $this->alertFlag($request, $validated['role']),
            'password' => Hash::make($validated['password']),
            // Choisi par l'admin, donc connu de deux personnes : à remplacer à la 1re connexion.
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        ActivityLog::record('user.created', "Création de l'utilisateur {$user->name} ({$user->role_label})", $user, ['role' => $user->role->value]);

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
            'is_department_head' => ['nullable', 'boolean'],
            ...$this->alertRules($request),
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_department_head'] = $this->departmentHeadFlag($request, $validated['role']);
        $validated['receives_maintenance_alerts'] = $this->alertFlag($request, $validated['role']);
        $validated['phone'] = $this->normalizePhone($validated['phone'] ?? null);

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

        // Décocher « Compte actif » sur quelqu'un qui a encore des OT en cours les
        // rendrait orphelins : on enregistre le reste, puis on passe par l'écran
        // de réaffectation, qui fera la désactivation.
        $mustReassign = $user->is_active && ! $validated['is_active']
            && $user->assignedWorkOrders()->open()->exists();
        if ($mustReassign) {
            $validated['is_active'] = true;
        }

        $oldRole = $user->role;
        $wasActive = $user->is_active;
        $wasHead = $user->is_department_head;
        $receivedAlerts = $user->receives_maintenance_alerts;
        $oldIdentity = $user->only(['name', 'email']);

        $user->update($validated);

        // L'e-mail sert à se connecter et à recevoir le lien « mot de passe oublié » :
        // le changer en silence permettrait de s'approprier le compte d'un collègue.
        $identityChanges = collect($oldIdentity)
            ->filter(fn ($old, $field) => $old !== $user->{$field})
            ->map(fn ($old, $field) => ['from' => $old, 'to' => $user->{$field}]);
        if ($identityChanges->isNotEmpty()) {
            ActivityLog::record(
                'user.identity_changed',
                "Modification de l'identité de {$user->name} : "
                    .$identityChanges->map(fn ($c, $field) => ($field === 'email' ? 'e-mail' : 'nom')." {$c['from']} → {$c['to']}")->implode(', '),
                $user,
                [...$identityChanges->all(), 'target_role' => $oldRole->value],
            );
        }

        if ($user->role->seesAllWorkOrders() && $receivedAlerts !== $user->receives_maintenance_alerts) {
            ActivityLog::record(
                'user.alerts_changed',
                $user->receives_maintenance_alerts
                    ? "{$user->name} reçoit désormais les alertes de maintenance (astreinte)"
                    : "{$user->name} ne reçoit plus les alertes de maintenance (astreinte)",
                $user,
            );
        }

        if ($wasHead !== $user->is_department_head) {
            ActivityLog::record(
                'user.department_head_changed',
                $user->is_department_head
                    ? "{$user->name} désigné(e) responsable du service {$user->role->label()}"
                    : "{$user->name} n'est plus responsable de service",
                $user,
            );
        }

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

        if ($mustReassign) {
            return redirect()->route('users.deactivate', $user)
                ->with('warning', "Modifications enregistrées. {$user->name} a encore des ordres de travail en cours : confiez-les à quelqu'un pour terminer la désactivation.");
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user, DeactivateUser $deactivate): RedirectResponse
    {
        // Sécurité : on empêche un admin de se désactiver lui-même par erreur
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if ($user->isLastActiveAdmin()) {
            return back()->with('warning', "C'est le dernier administrateur actif : il ne peut pas être désactivé.");
        }

        // Du travail en cours : il faut d'abord choisir qui le reprend.
        if ($user->assignedWorkOrders()->open()->exists()) {
            return redirect()->route('users.deactivate', $user);
        }

        // Même chemin que l'écran de départ (plans préventifs, chronomètre, journal).
        $deactivate->handle($user, null);

        return back()->with('success', 'Utilisateur désactivé.');
    }

    /**
     * Le téléphone est obligatoire pour qui reçoit les alertes d'astreinte :
     * sans numéro, une alerte de nuit n'a nulle part où partir.
     */
    private function alertRules(Request $request): array
    {
        $receivesAlerts = in_array($request->role, [UserRole::Admin->value, UserRole::Manager->value], true)
            && $request->boolean('receives_maintenance_alerts');

        return [
            'phone' => [$receivesAlerts ? 'required' : 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 .\-]{7,}$/'],
            'receives_maintenance_alerts' => ['nullable', 'boolean'],
        ];
    }

    /** Sans objet hors admins/managers : on garde la valeur par défaut (vrai). */
    private function alertFlag(Request $request, string $role): bool
    {
        return UserRole::from($role)->seesAllWorkOrders()
            ? $request->boolean('receives_maintenance_alerts')
            : true;
    }

    /** "07 07 12 34 56" et "07.07.12.34.56" sont stockés "0707123456". */
    private function normalizePhone(?string $phone): ?string
    {
        return blank($phone) ? null : preg_replace('/[\s.\-]/', '', $phone);
    }

    /**
     * Le statut de responsable n'existe que pour les services qui en ont un : on le
     * remet à faux pour les autres rôles (ex. une responsable HK promue manager).
     */
    private function departmentHeadFlag(Request $request, string $role): bool
    {
        return $request->boolean('is_department_head') && UserRole::from($role)->hasDepartmentHead();
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
