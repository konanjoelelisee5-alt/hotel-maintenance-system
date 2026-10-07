<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\DashboardPanels;
use App\Support\Housekeeping;
use App\Support\ReceptionDesk;
use App\Support\RoomActivity;
use App\Support\SystemAlerts;
use App\Support\WorkQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Dashboards par rôle. Le contenu vient des classes de App\Support : WorkQueue (file
 * de travail), DashboardPanels (indicateurs et panneaux), SystemAlerts (admin),
 * ReceptionDesk (accueil de la réception).
 */
class DashboardController extends Controller
{
    public function admin(Request $request): View
    {
        return $this->render('dashboards.admin', $request)
            ->with('systemAlerts', SystemAlerts::forAdmin())
            ->with($this->pilotPanels());
    }

    public function manager(Request $request): View
    {
        return $this->render('dashboards.manager', $request)->with($this->pilotPanels());
    }

    public function technicien(Request $request): View
    {
        $user = $request->user();

        // Sa courbe : les réparations qu'il a terminées, jour par jour.
        return $this->render('dashboards.technicien', $request)->with([
            'chart' => RoomActivity::weekChart(fn ($q) => $q->where('assigned_to', $user->id), roomsOnly: false, dateColumn: 'completed_at'),
            'activity' => RoomActivity::feed(fn ($q) => $q->visibleTo($user), withBlocks: false),
            'onCall' => ReceptionDesk::onCall(),
        ]);
    }

    /**
     * Admin et manager : tous les ordres créés jour par jour, ce qui vient de se passer dans
     * les chambres, et l'astreinte du moment.
     *
     * @return array<string, mixed>
     */
    private function pilotPanels(): array
    {
        return [
            'chart' => RoomActivity::weekChart(roomsOnly: false),
            'activity' => RoomActivity::feed(),
            'onCall' => ReceptionDesk::onCall(),
        ];
    }

    public function housekeeping(Request $request): View
    {
        $user = $request->user();
        // Les signalements que la personne voit : les siens, ou ceux de l'équipe (gouvernante).
        $scope = fn ($query) => $query->visibleTo($user);

        return $this->render('dashboards.housekeeping', $request)->with([
            'chart' => RoomActivity::weekChart($scope),
            'activity' => RoomActivity::feed($scope, withBlocks: $user->isDepartmentHead()),
            'onCall' => ReceptionDesk::onCall(),
            'teamLoad' => $user->isDepartmentHead() ? Housekeeping::teamLoad($user) : collect(),
        ]);
    }

    /**
     * Accueil de la réception : la recherche de chambre (?chambre=305) en tête, puis ce
     * qui concerne les clients maintenant, l'astreinte à appeler et les blocages à décider.
     */
    public function reception(Request $request): View
    {
        return $this->render('dashboards.reception', $request)
            ->with(ReceptionDesk::dashboard($request->user(), trim($request->string('chambre')->toString())));
    }

    /**
     * Données communes « pulse / file d'attente / panneaux latéraux / timeline »
     * pour la vue de dashboard du rôle courant.
     */
    private function render(string $view, Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        // Housekeeping : l'agent arrive sur ses propres signalements (« Les miens »).
        $filter = $request->string('filter')->toString() ?: ($user->role === UserRole::Housekeeping ? 'mine' : 'urgent');
        $period = array_key_exists($request->string('period')->toString(), DashboardPanels::PERIODS)
            ? $request->string('period')->toString()
            : '7d';
        $filters = WorkQueue::tabs($user);

        return view($view, [
            'pulse' => DashboardPanels::pulse($user),
            'queue' => WorkQueue::top($user, $filter),
            'filter' => $filter,
            'filters' => $filters,
            'filterCounts' => collect($filters)->mapWithKeys(fn (array $f) => [$f['key'] => WorkQueue::filtered($user, $f['key'])->count()]),
            'period' => $period,
            'periods' => DashboardPanels::PERIODS,
            'sideA' => DashboardPanels::sideA($user, $period),
            'sideB' => DashboardPanels::sideB($user),
            'timeline' => DashboardPanels::timeline($user),
            // Service demandeur : réparations à confirmer ou à rouvrir.
            'toConfirm' => in_array($user->role, [UserRole::Housekeeping, UserRole::Reception], true)
                ? WorkOrder::visibleTo($user)->awaitingRequesterConfirmation()->with(['room', 'assignee'])->latest('completed_at')->get()
                : collect(),
            // Gouvernante : chambres où la même panne revient (Housekeeping::REPEAT_DAYS jours).
            'repeats' => $user->role === UserRole::Housekeeping && $user->isDepartmentHead()
                ? Housekeeping::repeats($user)
                : collect(),
        ]);
    }
}
