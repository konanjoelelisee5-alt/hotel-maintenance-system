<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\WorkOrder;
use App\Support\MonthlyReport;
use App\Support\ReceptionDesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Support\OpenAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestes de la réception sur une chambre en panne : déclarer la situation du client
 * (dans la chambre, sorti, relogé, arrivée prévue, parti). L'OT n'est pas modifié
 * dans son contenu : seules l'occupation et l'échéance suivent, avec une note.
 */
class ReceptionController extends Controller
{
    public function updateSituation(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'situation' => ['required', Rule::in(array_keys(ReceptionDesk::SITUATIONS))],
            'time' => ['nullable', 'required_if:situation,sorti,arrivee', 'date_format:H:i'],
            'other_room' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:300'],
        ], [
            'situation.required' => 'Choisissez la situation du client.',
            'time.required_if' => 'Indiquez l\'heure (retour ou arrivée du client).',
            'time.date_format' => 'Heure invalide.',
        ]);

        abort_unless(WorkOrder::open()->where('room_id', $room->id)->exists(), 422, 'Aucune panne en cours dans cette chambre.');

        ReceptionDesk::updateSituation($room, $data['situation'], $data['time'] ?? null, $data['other_room'] ?? null, $data['note'] ?? null, $request->user());

        return redirect()->route('reception.dashboard', ['chambre' => $room->number])
            ->with('success', 'Situation du client enregistrée : la maintenance est informée.');
    }

    /**
     * État de l'accueil, interrogé toutes les 30 s par l'écran du comptoir : une
     * empreinte de ce qui est affiché (clients concernés, blocages, réparations à
     * confirmer) et le nombre de notifications non lues. L'écran se recharge si
     * l'empreinte change, et sonne si une notification arrive.
     */
    public function state(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'signature' => ReceptionDesk::signature($user),
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Bilan du mois de la responsable de réception (demandes de son équipe). */
    public function monthlyReport(Request $request): View
    {
        $user = $request->user();
        abort_unless(OpenAccess::enabled() || $user->isDepartmentHead(), 403);
        $month = MonthlyReport::month($request);

        return view('housekeeping.monthly-report', [
            'month' => $month,
            'canGoNext' => $month->copy()->addMonth()->lte(now()->startOfMonth()),
            'stats' => MonthlyReport::stats($user, $month),
            'previous' => MonthlyReport::stats($user, $month->copy()->subMonth()),
            'reportRoute' => 'reception.monthly-report',
            'service' => 'Réception',
            'crumb' => 'Responsable de réception',
            'inspections' => null,
        ]);
    }
}
