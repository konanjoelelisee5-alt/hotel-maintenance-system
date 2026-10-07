<?php

namespace App\Support;

use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderStatusHistory;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * La vie des chambres pour les accueils de la feuille (réception, Housekeeping) : la courbe
 * des pannes signalées sur 7 jours et le fil d'activité. $scope restreint les ordres de
 * travail pris en compte (ex. fn ($q) => $q->visibleTo($user) : les siens, ou ceux de
 * l'équipe pour une responsable) ; sans $scope, toutes les chambres de l'hôtel.
 */
class RoomActivity
{
    /**
     * Ordres de travail jour par jour (par défaut : pannes signalées dans les chambres) : les
     * 7 derniers jours face aux 7 jours d'avant. $roomsOnly = false compte aussi les espaces
     * communs et les équipements ; $dateColumn = 'completed_at' compte les réparations terminées.
     *
     * @return array{days: array<int, array{label: string, day: string, date: string, now: int, before: int}>, max: int, total: int, totalBefore: int, range: string}
     */
    public static function weekChart(?Closure $scope = null, bool $roomsOnly = true, string $dateColumn = 'created_at'): array
    {
        $counts = self::orders($scope, $roomsOnly)->where($dateColumn, '>=', now()->subDays(13)->startOfDay())
            ->get([$dateColumn])
            ->countBy(fn (WorkOrder $w) => $w->{$dateColumn}->toDateString());

        $days = collect(range(6, 0))->map(function (int $ago) use ($counts) {
            $day = now()->subDays($ago);

            return [
                'label' => $day->locale('fr')->isoFormat('ddd'),
                'day' => $day->format('d'),
                'date' => $day->locale('fr')->isoFormat('dddd D MMMM'),
                'now' => $counts[$day->toDateString()] ?? 0,
                'before' => $counts[$day->copy()->subDays(7)->toDateString()] ?? 0,
            ];
        });

        return [
            'days' => $days->all(),
            'max' => max(4, $days->max('now'), $days->max('before')),
            'total' => $days->sum('now'),
            'totalBefore' => $days->sum('before'),
            'range' => now()->subDays(6)->locale('fr')->isoFormat('D MMM').' – '.now()->locale('fr')->isoFormat('D MMM'),
        ];
    }

    /**
     * Ce qui vient de se passer : signalements (avec la description), photos et messages
     * vocaux, réparations, et demandes de blocage ($withBlocks).
     *
     * @return Collection<int, array{at: Carbon, who: ?User, verb: string, room: ?Room, quote: ?string, file: ?array{name: string, size: string, kind: string}, tone: string}>
     */
    public static function feed(?Closure $scope = null, bool $withBlocks = true, int $limit = 5): Collection
    {
        $inScope = fn ($q) => $scope ? $scope($q->whereNotNull('room_id')) : $q->whereNotNull('room_id');

        $reported = self::orders($scope)->with('reporter', 'room')->latest()->limit($limit)->get()
            ->map(fn (WorkOrder $w) => ['at' => $w->created_at, 'who' => $w->reporter, 'verb' => 'a signalé une panne', 'room' => $w->room,
                // Le texte posé par défaut sur un signalement rapide n'apprend rien : pas de bulle.
                'quote' => filled($w->description) && ! str_starts_with($w->description, 'Signalement rapide sans description')
                    ? Str::limit(trim($w->description), 140) : null, 'file' => null, 'tone' => 'red']);

        $repaired = WorkOrderStatusHistory::where('new_status', 'resolu')->whereHas('workOrder', $inScope)
            ->with('changedBy', 'workOrder.room')->latest()->limit($limit)->get()
            ->map(fn (WorkOrderStatusHistory $h) => ['at' => $h->created_at, 'who' => $h->changedBy, 'verb' => 'a réparé', 'room' => $h->workOrder?->room,
                'quote' => null, 'file' => null, 'tone' => 'green']);

        $files = WorkOrderAttachment::whereHas('workOrder', $inScope)
            ->with('uploader', 'workOrder.room')->latest()->limit($limit)->get()
            ->map(fn (WorkOrderAttachment $a) => ['at' => $a->created_at, 'who' => $a->uploader,
                'verb' => str_starts_with((string) $a->mime_type, 'audio/') ? 'a laissé un message vocal' : 'a ajouté une photo',
                'room' => $a->workOrder?->room, 'quote' => null, 'tone' => 'blue',
                'file' => ['name' => $a->original_name, 'size' => self::fileSize((int) $a->size), 'kind' => str_starts_with((string) $a->mime_type, 'audio/') ? 'mic' : 'camera']]);

        $blocks = $withBlocks
            ? RoomBlock::with('requester', 'room')->latest()->limit($limit)->get()
                ->map(fn (RoomBlock $b) => ['at' => $b->created_at, 'who' => $b->requester, 'verb' => 'a demandé un blocage', 'room' => $b->room,
                    'quote' => $b->reason, 'file' => null, 'tone' => 'amber'])
            : collect();

        return $reported->concat($repaired)->concat($files)->concat($blocks)
            ->sortByDesc('at')->take($limit)->values();
    }

    private static function orders(?Closure $scope, bool $roomsOnly = true)
    {
        $query = $roomsOnly ? WorkOrder::whereNotNull('room_id') : WorkOrder::query();

        return $scope ? $scope($query) : $query;
    }

    /** Taille lisible d'un fichier (sans l'extension intl de PHP, absente sur certains serveurs). */
    private static function fileSize(int $bytes): string
    {
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', ' ').' Mo' : max(1, (int) round($bytes / 1024)).' Ko';
    }
}
