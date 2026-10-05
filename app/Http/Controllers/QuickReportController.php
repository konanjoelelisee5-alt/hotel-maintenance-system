<?php

namespace App\Http\Controllers;

use App\Actions\ReportWorkOrder;
use App\Enums\IssueCategory;
use App\Enums\RoomOccupancy;
use App\Enums\UserRole;
use App\Http\Requests\ComplementQuickReportRequest;
use App\Http\Requests\StoreQuickReportRequest;
use App\Models\ActivityLog;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\GuestRoomAtRiskNotification;
use App\Notifications\HousekeepingReportNotification;
use App\Support\Housekeeping;
use App\Support\OnCall;
use App\Support\ReceptionDesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Signalement rapide (« mégaphone ») : chambre, pictogramme, message vocal,
 * photo. Pensé pour le personnel d'étage qui lit ou écrit difficilement —
 * aucun champ technique, le manager requalifie l'OT au dispatch.
 */
class QuickReportController extends Controller
{
    public function create(Request $request): View
    {
        return view($this->screen($request, 'create'), [
            // number => étage : le contrôle du numéro se fait sur le téléphone, sans aller-retour.
            // Seules les chambres en service : on ne signale pas dans une chambre retirée.
            'rooms' => Room::rooms()->inService()->orderBy('number')->pluck('floor', 'number'),
            // Affichage seulement : dire « hors service » plutôt que « inexistante » (l'envoi reste refusé).
            'outOfServiceRooms' => Room::rooms()->where('status', 'hors_service')->pluck('number'),
            'commonAreas' => Room::commonAreas()->inService()->get()
                ->map(fn (Room $r) => ['id' => $r->id, 'label' => $r->label])->sortBy('label')->values(),
            'categories' => IssueCategory::cases(),
            'occupancies' => RoomOccupancy::cases(),
            // Pré-rempli par ?chambre=214 (QR code collé dans la chambre).
            'prefillRoom' => $request->string('chambre')->toString(),
            'maxSeconds' => StoreQuickReportRequest::MAX_AUDIO_SECONDS,
            // Housekeeping : signalements déjà ouverts, pour avertir d'un doublon pendant la saisie.
            'openReports' => $request->user()->role === UserRole::Housekeeping
                ? Housekeeping::openReportsByPlace($request->user())
                : [],
        ]);
    }

    public function store(StoreQuickReportRequest $request, ReportWorkOrder $report): JsonResponse|RedirectResponse
    {
        $category = IssueCategory::from($request->validated('category'));
        // Espace commun : celui choisi dans la liste, ou aucun (« Autre endroit », précisé à la voix).
        $room = $request->boolean('common_area')
            ? Room::find($request->validated('common_area_id'))
            : Room::rooms()->where('number', $request->validated('room_number'))->first();
        $audio = $request->file('audio');
        $note = trim((string) $request->validated('note'));
        $urgent = $request->boolean('urgent');
        // L'occupation n'a de sens que pour une chambre.
        $occupancy = $room && ! $room->isCommonArea()
            ? RoomOccupancy::from($request->validated('room_occupancy'))
            : null;

        $workOrder = $report->handle($request->user(), [
            'title' => $category->label().' · '.($room?->label ?? 'Parties communes')
                .($audio ? ' (message vocal)' : ''),
            'description' => $note !== '' ? $note : ($audio
                ? 'Message vocal joint : écouter l\'enregistrement dans les pièces jointes.'
                : 'Signalement rapide sans description.'),
            'room_id' => $room?->id,
            'room_occupancy' => $occupancy,
            // Client sorti : la réparation doit être faite avant son retour.
            'due_date' => $occupancy?->repairDeadline(now()),
            'type_id' => WorkOrderType::where('code', 'maintenance')->value('id'),
            'priority_id' => WorkOrderPriority::where('code', $urgent ? 'urgente' : 'moyenne')->value('id'),
        ]);

        if ($audio) {
            // Le serveur voit souvent un enregistrement sans image comme "video/webm" :
            // on le range en audio pour que la fiche affiche un lecteur.
            $mime = 'audio/'.Str::after(Str::before($audio->getMimeType() ?? 'audio/webm', ';'), '/');
            $this->attach($workOrder, $audio, 'message-vocal.'.($audio->extension() ?: 'webm'), $mime);
        }

        if ($photo = $request->file('photo')) {
            $this->attach($workOrder, $photo, 'photo.'.($photo->extension() ?: 'jpg'), $photo->getMimeType() ?? 'image/jpeg');
        }

        // Panne urgente avec le client dans la chambre : la réception gère tout de suite.
        if ($urgent && $occupancy === RoomOccupancy::ClientPresent) {
            ReceptionDesk::alertGuestAtRisk($workOrder, GuestRoomAtRiskNotification::GUEST_INSIDE);
        }

        // Urgence d'un agent : sa gouvernante le sait tout de suite (pas seulement à l'affectation).
        if ($urgent && $request->user()->role === UserRole::Housekeeping) {
            Notification::send(
                Housekeeping::heads()->reject(fn (User $u) => $u->id === $request->user()->id),
                new HousekeepingReportNotification($workOrder->load('room', 'reporter'), HousekeepingReportNotification::TEAM_URGENT, $occupancy?->label()),
            );
        }

        $sent = route('quick-reports.sent', $workOrder);
        // Housekeeping : toast de confirmation sur la page suivante (sauf envoi différé
        // de la boîte d'envoi : la page en cours affiche déjà son propre toast).
        if ($request->user()->role === UserRole::Housekeeping && ! $request->hasHeader('X-HK-Outbox')) {
            session()->flash('success', 'Signalement envoyé à la maintenance.');
        }

        return $request->expectsJson()
            ? response()->json(['redirect' => $sent], 201)
            : redirect($sent);
    }

    public function sent(Request $request, WorkOrder $workOrder): View
    {
        $this->authorize('view', $workOrder);

        return view($this->screen($request, 'sent'), ['workOrder' => $workOrder->load('room', 'priority')]);
    }

    /**
     * Housekeeping : l'agent retire un signalement fait par erreur (WorkOrderPolicy::withdraw).
     * Rien n'est supprimé : l'OT passe « annulé » et l'historique garde qui, quand et pourquoi.
     */
    public function withdraw(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('withdraw', $workOrder);
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Housekeeping::WITHDRAW_REASONS))],
            'detail' => ['nullable', 'string', 'max:300'],
        ], ['reason.required' => 'Choisissez pourquoi vous retirez ce signalement.']);

        $reason = Housekeeping::WITHDRAW_REASONS[$data['reason']].(filled($data['detail'] ?? null) ? ' — '.trim($data['detail']) : '');

        $workOrder->statusHistories()->create([
            'changed_by' => $request->user()->id,
            'old_status' => $workOrder->status,
            'new_status' => 'annule',
            'note' => "Retiré par le demandeur : {$reason}",
        ]);
        $workOrder->update(['status' => 'annule']);

        ActivityLog::record('work_order.withdrawn', "Signalement {$workOrder->code()} retiré par son auteur : {$reason}", $workOrder);

        // Ceux qui avaient été alertés ne doivent pas se déplacer pour rien : l'astreinte
        // (appelée sur son téléphone pour un OT urgent), la réception (client concerné).
        $workOrder->loadMissing('room', 'reporter', 'priority');
        $actor = $request->user()->id;
        if ($workOrder->priority?->triggersOnCallAlert()) {
            Notification::send(OnCall::recipients()->reject(fn (User $u) => $u->id === $actor),
                new HousekeepingReportNotification($workOrder, HousekeepingReportNotification::WITHDRAWN, Housekeeping::WITHDRAW_REASONS[$data['reason']], byPhone: true));
        }
        if ($workOrder->reception_alerted_at !== null) {
            Notification::send(ReceptionDesk::staff(),
                new HousekeepingReportNotification($workOrder, HousekeepingReportNotification::WITHDRAWN, Housekeeping::WITHDRAW_REASONS[$data['reason']]));
        }

        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Signalement retiré. Il reste visible dans votre historique.');
    }

    /**
     * Housekeeping : précision ajoutée à un signalement en cours (WorkOrderPolicy::complement).
     * Le texte devient un commentaire, le vocal et la photo des pièces jointes : la
     * maintenance les voit dans l'onglet « Échanges » de sa fiche.
     */
    public function complement(ComplementQuickReportRequest $request, WorkOrder $workOrder): JsonResponse|RedirectResponse
    {
        $this->authorize('complement', $workOrder);
        $user = $request->user();
        $note = trim((string) $request->validated('note'));
        $audio = $request->file('audio');
        $photo = $request->file('photo');

        $workOrder->comments()->create([
            'user_id' => $user->id,
            'content' => 'Précision du demandeur : '.($note !== '' ? $note : collect([$audio ? 'message vocal' : null, $photo ? 'photo' : null])->filter()->join(' et ').' ajouté(s).'),
        ]);

        $stamp = now()->format('His');
        if ($audio) {
            $mime = 'audio/'.Str::after(Str::before($audio->getMimeType() ?? 'audio/webm', ';'), '/');
            $this->attach($workOrder, $audio, "precision-vocale-{$stamp}.".($audio->extension() ?: 'webm'), $mime, $user->id);
        }
        if ($photo) {
            $this->attach($workOrder, $photo, "precision-photo-{$stamp}.".($photo->extension() ?: 'jpg'), $photo->getMimeType() ?? 'image/jpeg', $user->id);
        }

        // Le technicien affecté la lit tout de suite ; sans technicien, l'équipe qui dispatche.
        $recipients = $workOrder->assignee ? collect([$workOrder->assignee]) : OnCall::recipients();
        Notification::send($recipients->reject(fn (User $u) => $u->id === $user->id),
            new HousekeepingReportNotification($workOrder->loadMissing('room', 'reporter'), HousekeepingReportNotification::COMPLEMENTED,
                $note !== '' ? Str::limit($note, 120) : collect([$audio ? 'message vocal' : null, $photo ? 'photo' : null])->filter()->join(' et ')));

        session()->flash('success', 'Précision ajoutée au signalement.');
        $back = route('work-orders.show', $workOrder);

        return $request->expectsJson()
            ? response()->json(['redirect' => $back], 201)
            : redirect($back);
    }

    /** Housekeeping : parcours en 4 étapes ; les autres services gardent l'écran d'une page. */
    private function screen(Request $request, string $name): string
    {
        return $request->user()->role === UserRole::Housekeeping
            ? "quick-reports.{$name}"
            : "quick-reports.classic-{$name}";
    }

    private function attach(WorkOrder $workOrder, UploadedFile $file, string $name, string $mime, ?int $uploader = null): void
    {
        $workOrder->attachments()->create([
            'uploaded_by' => $uploader ?? $workOrder->reported_by,
            'file_path' => $file->store('work-orders/'.$workOrder->id, FileDownloadController::DISK),
            'original_name' => $name,
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ]);
    }
}
