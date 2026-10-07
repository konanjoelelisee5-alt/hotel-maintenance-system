<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\RoomInspection;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Housekeeping;
use App\Support\MonthlyReport;
use App\Support\RoomInspectionChecklist;
use App\Support\OpenAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Outils de la gouvernante : le plan des étages (l'état de chaque chambre d'un coup
 * d'œil) et le bilan du mois (ce que l'équipe a signalé, et en combien de temps
 * c'est réparé).
 */
class HousekeepingSupervisionController extends Controller
{
    public function floorPlan(Request $request): View
    {
        $user = $this->head($request);

        $rooms = Room::rooms()->with(['blocks' => fn ($q) => $q->active()])->get();
        $open = WorkOrder::open()->whereIn('room_id', $rooms->pluck('id'))->with('priority', 'room')->latest()->get()->groupBy('room_id');
        // La gouvernante ne voit le détail que des signalements de son équipe ; les autres sont comptés.
        $visible = WorkOrder::visibleTo($user)->open()->pluck('id')->flip();
        $lastInspection = RoomInspection::done()->selectRaw('room_id, MAX(completed_at) as last_at')->groupBy('room_id')->pluck('last_at', 'room_id');

        $tiles = $rooms->map(function (Room $room) use ($open, $visible, $lastInspection) {
            $orders = $open->get($room->id, collect());
            $block = $room->blocks->first();
            $last = isset($lastInspection[$room->id]) ? Carbon::parse($lastInspection[$room->id]) : null;
            $alert = $orders->contains(fn (WorkOrder $w) => $w->priority?->code === 'urgente' || $w->slaSummary()['late']);

            return [
                'id' => $room->id,
                'number' => (string) $room->number,
                'floor' => $room->floor ?: 'Sans étage',
                'state' => match (true) {
                    $room->status === 'hors_service' => 'out',
                    $block?->status === RoomBlock::BLOCKED => 'blocked',
                    $alert => 'alert',
                    $orders->isNotEmpty() => 'issue',
                    default => 'ok',
                },
                'statusLabel' => $room->status_label,
                'block' => $block?->status_label,
                'orders' => $orders->filter(fn (WorkOrder $w) => $visible->has($w->id))->map(fn (WorkOrder $w) => [
                    'code' => $w->code(),
                    'label' => ($c = Housekeeping::category($w)) ? Housekeeping::categoryLabel($c) : $w->title,
                    'status' => Housekeeping::status($w->status)['label'],
                    'urgent' => $w->priority?->code === 'urgente',
                    'late' => $w->slaSummary()['late'],
                    'url' => route('work-orders.show', $w),
                ])->values(),
                'others' => $orders->reject(fn (WorkOrder $w) => $visible->has($w->id))->count(),
                'lastInspection' => $last?->locale('fr')->diffForHumans(),
                'inspectionDue' => ! $last || $last->lt(now()->subDays(RoomInspectionChecklist::DUE_AFTER_DAYS)),
            ];
        });

        $floors = $tiles->groupBy('floor')->sortKeysUsing('strnatcmp')
            ->map(fn (Collection $t) => $t->sortBy('number', SORT_NATURAL)->values());

        return view('housekeeping.floor-plan', [
            'floors' => $floors,
            'counts' => $tiles->countBy('state')->all() + ['due' => $tiles->where('inspectionDue', true)->count()],
            'total' => $tiles->count(),
        ]);
    }

    public function monthlyReport(Request $request): View
    {
        $user = $this->head($request);
        $month = MonthlyReport::month($request);

        $stats = MonthlyReport::stats($user, $month);
        $previous = MonthlyReport::stats($user, $month->copy()->subMonth());

        $inspections = RoomInspection::done()->whereBetween('completed_at', [$month, $month->copy()->endOfMonth()])->with('items')->get();
        $nokPoints = $inspections->flatMap->items->where('result', 'nok')->countBy('label')->sortDesc()->take(5);

        return view('housekeeping.monthly-report', [
            'month' => $month,
            'canGoNext' => $month->copy()->addMonth()->lte(now()->startOfMonth()),
            'stats' => $stats,
            'previous' => $previous,
            'reportRoute' => 'housekeeping.monthly-report',
            'service' => 'Housekeeping',
            'crumb' => 'Outils de la gouvernante',
            'inspections' => [
                'count' => $inspections->count(),
                'rooms' => $inspections->pluck('room_id')->unique()->count(),
                'conformity' => $inspections->isEmpty() ? null : (int) round($inspections->map->conformity()->filter(fn ($v) => $v !== null)->avg()),
                'nokPoints' => $nokPoints,
            ],
        ]);
    }

    private function head(Request $request): User
    {
        $user = $request->user();
        abort_unless(OpenAccess::enabled() || ($user->role === UserRole::Housekeeping && $user->isDepartmentHead()), 403);

        return $user;
    }
}
