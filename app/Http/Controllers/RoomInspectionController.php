<?php

namespace App\Http\Controllers;

use App\Actions\ReportWorkOrder;
use App\Enums\IssueCategory;
use App\Enums\UserRole;
use App\Models\Room;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\HousekeepingReportNotification;
use App\Support\OnCall;
use App\Support\RoomInspectionChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Support\OpenAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tournée d'inspection des chambres (gouvernante). Chaque point est enregistré dès
 * qu'il est noté (rien n'est perdu si le téléphone se met en veille) ; à la fin,
 * les non-conformités deviennent des OT, une par catégorie de panne, ou complètent
 * l'OT déjà ouvert pour la même panne dans la chambre.
 */
class RoomInspectionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeHead($request);

        $lastByRoom = RoomInspection::done()->selectRaw('room_id, MAX(completed_at) as last_at')->groupBy('room_id')->pluck('last_at', 'room_id');
        $rooms = Room::rooms()->inService()->orderBy('number')->get(['id', 'number', 'floor']);

        return view('housekeeping.inspections.index', [
            'inProgress' => RoomInspection::where('status', RoomInspection::IN_PROGRESS)->with('room', 'inspector', 'items')->latest()->get(),
            'recent' => RoomInspection::done()->with('room', 'inspector', 'items')->latest('completed_at')->limit(20)->get(),
            // À inspecter en priorité : jamais inspectées, puis les plus anciennes.
            'due' => $rooms
                ->map(fn (Room $r) => ['room' => $r, 'last' => isset($lastByRoom[$r->id]) ? Carbon::parse($lastByRoom[$r->id]) : null])
                ->filter(fn (array $r) => ! $r['last'] || $r['last']->lt(now()->subDays(RoomInspectionChecklist::DUE_AFTER_DAYS)))
                ->sortBy(fn (array $r) => $r['last']?->timestamp ?? 0)
                ->take(8)->values(),
            'roomNumbers' => $rooms->pluck('number'),
            'prefill' => $request->string('chambre')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHead($request);
        $data = $request->validate([
            'room_number' => ['required', Rule::exists('rooms', 'number')->where('type', Room::TYPE_ROOM)->whereNot('status', 'hors_service')],
        ], [
            'room_number.required' => 'Indiquez la chambre à inspecter.',
            'room_number.exists' => 'Cette chambre n\'existe pas ou est hors service.',
        ]);
        $room = Room::rooms()->where('number', $data['room_number'])->firstOrFail();

        // Une seule inspection en cours par chambre : on reprend celle qui existe.
        $inspection = RoomInspection::where('room_id', $room->id)->where('status', RoomInspection::IN_PROGRESS)->first();
        if (! $inspection) {
            $inspection = RoomInspection::create(['room_id' => $room->id, 'inspector_id' => $request->user()->id]);
            $inspection->items()->createMany(RoomInspectionChecklist::itemsForNewInspection());
        }

        return redirect()->route('inspections.show', $inspection);
    }

    public function show(Request $request, RoomInspection $inspection): View
    {
        $this->authorizeHead($request);

        return view('housekeeping.inspections.show', [
            'inspection' => $inspection->load('room', 'inspector', 'items.workOrder'),
            'previous' => RoomInspection::done()->where('room_id', $inspection->room_id)->whereKeyNot($inspection->id)->latest('completed_at')->first(),
        ]);
    }

    /** Enregistre un point dès qu'il est noté (appel fetch de l'écran d'inspection). */
    public function updatePoint(Request $request, RoomInspection $inspection, RoomInspectionItem $item): JsonResponse
    {
        $this->authorizeOpen($request, $inspection, $item);
        $data = $request->validate([
            'result' => ['nullable', Rule::in(['ok', 'nok', 'na'])],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);
        $item->update($data);

        return response()->json(['saved' => true, 'progress' => $inspection->load('items')->progress()]);
    }

    public function storePhoto(Request $request, RoomInspection $inspection, RoomInspectionItem $item): JsonResponse
    {
        $this->authorizeOpen($request, $inspection, $item);
        $request->validate(['photo' => ['required', 'image', 'max:10240']], ['photo.image' => 'La photo n\'a pas pu être lue.']);

        if ($item->photo_path) {
            Storage::disk(FileDownloadController::DISK)->delete($item->photo_path);
        }
        $item->update(['photo_path' => $request->file('photo')->store('room-inspections/'.$inspection->id, FileDownloadController::DISK)]);

        return response()->json(['saved' => true]);
    }

    /** Clôt l'inspection : les points non conformes deviennent des OT (ou complètent l'OT ouvert). */
    public function complete(Request $request, RoomInspection $inspection, ReportWorkOrder $report): RedirectResponse
    {
        $this->authorizeOpen($request, $inspection);
        $inspection->load('room', 'items');
        abort_if($inspection->items->contains('result', null), 422, 'Notez tous les points avant de terminer.');
        $notes = $request->validate(['notes' => ['nullable', 'string', 'max:1000']])['notes'] ?? null;

        $created = 0;
        $completed = 0;
        foreach ($inspection->items->where('result', 'nok')->groupBy('category') as $categoryValue => $items) {
            [$workOrder, $isNew] = $this->reportFindings($inspection, IssueCategory::from($categoryValue), $items, $request->user(), $report);
            $items->each(fn (RoomInspectionItem $i) => $i->update(['work_order_id' => $workOrder->id]));
            $isNew ? $created++ : $completed++;
        }

        $inspection->update(['status' => RoomInspection::DONE, 'completed_at' => now(), 'notes' => $notes]);

        $summary = $created + $completed === 0
            ? 'Inspection terminée : tout est conforme.'
            : "Inspection terminée : {$created} OT créé(s)".($completed ? ", {$completed} OT existant(s) complété(s)" : '').'.';

        return redirect()->route('inspections.show', $inspection)->with('success', $summary);
    }

    /** Abandonne une inspection en cours (rien n'a encore été transmis à la maintenance). */
    public function destroy(Request $request, RoomInspection $inspection): RedirectResponse
    {
        $this->authorizeOpen($request, $inspection);
        Storage::disk(FileDownloadController::DISK)->deleteDirectory('room-inspections/'.$inspection->id);
        $inspection->delete();

        return redirect()->route('inspections.index')->with('success', 'Inspection abandonnée.');
    }

    /**
     * Une catégorie de non-conformités → un OT. Si un OT de même catégorie est déjà
     * ouvert dans la chambre, le constat s'y ajoute (pas de doublon) et le technicien
     * affecté, ou l'astreinte, en est prévenu.
     *
     * @param  Collection<int, RoomInspectionItem>  $items
     * @return array{0: WorkOrder, 1: bool}
     */
    private function reportFindings(RoomInspection $inspection, IssueCategory $category, Collection $items, User $head, ReportWorkOrder $report): array
    {
        $room = $inspection->room;
        $lines = $items->map(fn (RoomInspectionItem $i) => '- '.$i->label.($i->comment ? ' : '.$i->comment : ''))->join("\n");
        $text = 'Constat d\'inspection du '.now()->format('d/m/Y')." ({$head->name}) :\n{$lines}";

        $existing = WorkOrder::open()->where('room_id', $room->id)->where('title', 'like', $category->label().' · %')->latest()->first();

        if ($existing) {
            $existing->comments()->create(['user_id' => $head->id, 'content' => $text]);
            $this->attachPhotos($existing, $items, $head);
            $recipients = $existing->assignee ? collect([$existing->assignee]) : OnCall::recipients();
            Notification::send($recipients->reject(fn (User $u) => $u->id === $head->id),
                new HousekeepingReportNotification($existing->loadMissing('room', 'reporter'), HousekeepingReportNotification::COMPLEMENTED, 'constat d\'inspection'));

            return [$existing, false];
        }

        $workOrder = $report->handle($head, [
            'title' => $category->label().' · '.$room->label,
            'description' => $text,
            'room_id' => $room->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->value('id'),
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->value('id'),
        ]);
        $this->attachPhotos($workOrder, $items, $head);

        return [$workOrder, true];
    }

    /** Les photos des points deviennent des pièces jointes de l'OT (copie : l'inspection garde les siennes). */
    private function attachPhotos(WorkOrder $workOrder, Collection $items, User $head): void
    {
        $disk = Storage::disk(FileDownloadController::DISK);
        foreach ($items->whereNotNull('photo_path') as $item) {
            $target = 'work-orders/'.$workOrder->id.'/'.basename($item->photo_path);
            $disk->copy($item->photo_path, $target);
            $workOrder->attachments()->create([
                'uploaded_by' => $head->id,
                'file_path' => $target,
                'original_name' => 'inspection-'.$item->point_key.'.'.pathinfo($item->photo_path, PATHINFO_EXTENSION),
                'mime_type' => $disk->mimeType($target) ?: 'image/jpeg',
                'size' => $disk->size($target),
            ]);
        }
    }

    private function authorizeHead(Request $request): void
    {
        abort_unless(OpenAccess::enabled() || ($request->user()->isDepartmentHead() && $request->user()->role === UserRole::Housekeeping), 403);
    }

    private function authorizeOpen(Request $request, RoomInspection $inspection, ?RoomInspectionItem $item = null): void
    {
        $this->authorizeHead($request);
        abort_if($inspection->isDone(), 403, 'Cette inspection est terminée.');
        abort_if($item && $item->room_inspection_id !== $inspection->id, 404);
    }
}
