<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Housekeeping;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Liste et fiche des signalements pour le rôle Housekeeping. Les routes restent
 * work-orders.index / work-orders.show : WorkOrderController passe la main ici
 * (droits déjà vérifiés par lui) quand l'utilisateur est du Housekeeping.
 */
class HousekeepingWorkOrderController extends Controller
{
    /**
     * Écran « Signalements » du Housekeeping : deux vues, « En cours » (filtres de
     * statut) et « Historique » (terminés, regroupés par jour comme l'historique de
     * Chrome), recherche commune et, si un OT est ouvert, sa fiche en lecture seule.
     * La gouvernante garde « Les miens / Toute l'équipe ».
     */
    public function screen(Request $request, User $user, ?WorkOrder $selected = null): View
    {
        $filter = $request->string('filter')->toString() === 'mine' ? 'mine' : 'all';
        $q = trim($request->string('q')->toString());
        // Sans choix explicite, la fiche ouverte décide de la vue : un OT terminé est dans l'historique.
        $vue = match ($request->string('vue')->toString()) {
            'historique' => 'historique',
            'encours' => 'encours',
            default => $selected && in_array($selected->status, Housekeeping::FINISHED, true) ? 'historique' : 'encours',
        };
        // Filtres de chaque vue : clé => statuts.
        $etats = $vue === 'historique'
            ? ['done' => Housekeeping::GROUPS['done'], 'annule' => ['annule']]
            : ['pending' => Housekeeping::GROUPS['pending'], 'progress' => Housekeeping::GROUPS['progress']];
        $etat = array_key_exists($request->string('etat')->toString(), $etats) ? $request->string('etat')->toString() : '';

        $searched = WorkOrder::visibleTo($user)
            ->when($filter === 'mine' && $user->isDepartmentHead(), fn ($qr) => $qr->where('reported_by', $user->id))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhereHas('room', fn ($r) => $r->where('number', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
                // Référence « OT-00042 » ou « 42 ».
                ->when(ctype_digit(ltrim(str_ireplace('OT-', '', $q), '0')), fn ($c) => $c->orWhere('id', (int) ltrim(str_ireplace('OT-', '', $q), '0')))
            ));

        $inVue = fn ($qr, string $v) => $v === 'historique'
            ? $qr->whereIn('status', Housekeeping::FINISHED)
            : $qr->whereNotIn('status', Housekeeping::FINISHED);
        $vueCounts = [
            'encours' => $inVue(clone $searched, 'encours')->count(),
            'historique' => $inVue(clone $searched, 'historique')->count(),
        ];
        $counts = collect($etats)
            ->map(fn (array $statuses) => (clone $searched)->whereIn('status', $statuses)->count())
            ->prepend($vueCounts[$vue], '');

        $workOrders = $inVue(clone $searched, $vue)
            ->when($etat !== '', fn ($qr) => $qr->whereIn('status', $etats[$etat]))
            ->with(['room', 'assignee', 'reporter', 'priority'])
            // Historique : le plus récemment terminé d'abord ; en cours : le plus récemment signalé.
            ->when($vue === 'historique',
                fn ($qr) => $qr->orderByRaw('COALESCE(completed_at, updated_at) DESC'),
                fn ($qr) => $qr->latest())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $selected?->load([
            'room', 'assignee', 'reporter', 'priority', 'requesterConfirmedBy',
            'attachments.uploader', 'comments.user', 'interventionReport.technician',
            'statusHistories' => fn ($h) => $h->where('new_status', 'annule')->with('changedBy'),
        ]);

        return view('housekeeping.work-orders', [
            'workOrders' => $workOrders,
            'selected' => $selected,
            'repeatCount' => $selected ? Housekeeping::repeatCount($selected) : 0,
            'filter' => $filter,
            'q' => $q,
            'vue' => $vue,
            'vueCounts' => $vueCounts,
            'etat' => $etat,
            'etatLabels' => $vue === 'historique'
                ? ['' => 'Tous', 'done' => 'Réparés', 'annule' => 'Annulés ou retirés']
                : ['' => 'Tous', 'pending' => 'En attente', 'progress' => 'En cours'],
            'counts' => $counts,
            'listTitle' => Navigation::housekeepingListLabel($user),
            // Filtres conservés d'un OT à l'autre et au retour vers la liste.
            'listQuery' => array_filter([
                'filter' => $filter === 'mine' ? 'mine' : null, 'vue' => $vue === 'historique' ? 'historique' : null,
                'q' => $q, 'etat' => $etat, 'page' => $request->integer('page') > 1 ? $request->integer('page') : null,
            ]),
        ]);
    }
}
