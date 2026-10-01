<?php

namespace App\Http\Controllers;

use App\Actions\ReportWorkOrder;
use App\Enums\IssueCategory;
use App\Http\Requests\StoreQuickReportRequest;
use App\Models\Room;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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
        return view('quick-reports.create', [
            // number => étage : le contrôle du numéro se fait sur le téléphone, sans aller-retour.
            // Seules les chambres en service : on ne signale pas dans une chambre retirée.
            'rooms' => Room::rooms()->inService()->orderBy('number')->pluck('floor', 'number'),
            'commonAreas' => Room::commonAreas()->inService()->get()
                ->map(fn (Room $r) => ['id' => $r->id, 'label' => $r->label])->sortBy('label')->values(),
            'categories' => IssueCategory::cases(),
            // Pré-rempli par ?chambre=214 (QR code collé dans la chambre).
            'prefillRoom' => $request->string('chambre')->toString(),
            'maxSeconds' => StoreQuickReportRequest::MAX_AUDIO_SECONDS,
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

        $workOrder = $report->handle($request->user(), [
            'title' => $category->label().' · '.($room?->label ?? 'Parties communes')
                .($audio ? ' (message vocal)' : ''),
            'description' => $note !== '' ? $note : ($audio
                ? 'Message vocal joint : écouter l\'enregistrement dans les pièces jointes.'
                : 'Signalement rapide sans description.'),
            'room_id' => $room?->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->value('id'),
            'priority_id' => WorkOrderPriority::where('code', $request->boolean('urgent') ? 'urgente' : 'moyenne')->value('id'),
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

        $sent = route('quick-reports.sent', $workOrder);

        return $request->expectsJson()
            ? response()->json(['redirect' => $sent], 201)
            : redirect($sent);
    }

    public function sent(WorkOrder $workOrder): View
    {
        $this->authorize('view', $workOrder);

        return view('quick-reports.sent', ['workOrder' => $workOrder->load('room', 'priority')]);
    }

    private function attach(WorkOrder $workOrder, UploadedFile $file, string $name, string $mime): void
    {
        $workOrder->attachments()->create([
            'uploaded_by' => $workOrder->reported_by,
            'file_path' => $file->store('work-orders/'.$workOrder->id, 'public'),
            'original_name' => $name,
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ]);
    }
}
