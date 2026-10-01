<?php

namespace App\Http\Controllers;

use App\Actions\DeactivateUser;
use App\Models\User;
use App\Rules\ActiveTechnician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Départ d'un employé : écran de réaffectation de son travail, puis désactivation.
 * Méthode retenue : un remplaçant par défaut, modifiable OT par OT.
 */
class UserDeactivationController extends Controller
{
    public function create(User $user): View|RedirectResponse
    {
        if ($redirect = $this->refuse($user)) {
            return $redirect;
        }

        return view('users.deactivate', [
            'user' => $user,
            'workOrders' => $user->assignedWorkOrders()->open()->with(['room', 'equipment', 'priority', 'type'])->orderBy('sla_resolution_due_at')->get(),
            'planCount' => \App\Models\MaintenancePlan::where('assigned_to', $user->id)->count(),
            // Charge actuelle affichée à côté de chaque nom, pour ne pas surcharger un collègue.
            'technicians' => User::activeTechnicians()
                ->whereKeyNot($user->id)
                ->with('skills')
                ->withCount(['assignedWorkOrders as open_count' => fn ($q) => $q->open()])
                ->get(),
        ]);
    }

    public function store(Request $request, User $user, DeactivateUser $deactivate): RedirectResponse
    {
        if ($redirect = $this->refuse($user)) {
            return $redirect;
        }

        $validated = $request->validate([
            'default_replacement' => ['nullable', new ActiveTechnician(excludedId: $user->id)],
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['nullable', new ActiveTechnician(excludedId: $user->id)],
        ]);

        $summary = $deactivate->handle(
            $user,
            isset($validated['default_replacement']) ? (int) $validated['default_replacement'] : null,
            $validated['assignments'] ?? [],
        );

        $message = "{$user->name} a été désactivé(e).";
        if ($summary['reassigned'] > 0) {
            $message .= " {$summary['reassigned']} OT réaffecté(s).";
        }

        return redirect()->route('users.index')->with(
            $summary['unassigned'] > 0 ? 'warning' : 'success',
            $message.($summary['unassigned'] > 0
                ? " {$summary['unassigned']} OT sans remplaçant attendent une affectation dans la file des managers."
                : '')
        );
    }

    /** Mêmes garde-fous que la désactivation simple (cf. UserController). */
    private function refuse(User $user): ?RedirectResponse
    {
        return match (true) {
            $user->id === Auth::id() => redirect()->route('users.index')->with('warning', 'Vous ne pouvez pas désactiver votre propre compte.'),
            ! $user->is_active => redirect()->route('users.index')->with('warning', "{$user->name} est déjà désactivé(e)."),
            $user->isLastActiveAdmin() => redirect()->route('users.index')->with('warning', "C'est le dernier administrateur actif : il ne peut pas être désactivé."),
            default => null,
        };
    }
}
