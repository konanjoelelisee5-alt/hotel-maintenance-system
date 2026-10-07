<?php

namespace App\Support;

use App\Enums\RoomOccupancy;
use App\Enums\UserRole;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\GuestRoomAtRiskNotification;
use App\Notifications\GuestSituationNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * La réception : elle vend les chambres (dans Opera), valide leur blocage et gère
 * les clients. L'application la prévient, elle agit comme aujourd'hui.
 *
 * Elle voit l'état de toutes les chambres, quel que soit le service qui a signalé
 * la panne (lecture seule) : c'est elle qui répond au client.
 */
class ReceptionDesk
{
    /** Situations du client que la réception peut déclarer pour une chambre en panne. */
    public const SITUATIONS = [
        'present' => 'Le client est dans la chambre',
        'sorti' => 'Le client est sorti',
        'arrivee' => 'Chambre libre, arrivée prévue',
        'reloge' => 'Client relogé dans une autre chambre',
        'libre' => 'Chambre libre (client parti)',
    ];

    /** Fenêtre de l'historique récent affiché dans la recherche de chambre. */
    public const HISTORY_DAYS = 14;

    /**
     * @return Collection<int, User>
     */
    public static function staff(): Collection
    {
        return User::where('role', UserRole::Reception)->where('is_active', true)->get();
    }

    /** Prévient la réception une seule fois par OT qu'un client est concerné. */
    public static function alertGuestAtRisk(WorkOrder $workOrder, string $reason): void
    {
        if ($workOrder->reception_alerted_at !== null) {
            return;
        }

        Notification::send(self::staff(), new GuestRoomAtRiskNotification($workOrder->loadMissing('room'), $reason));
        $workOrder->forceFill(['reception_alerted_at' => now()])->saveQuietly();
    }

    /**
     * Ce que la réception peut dire au client sur une panne, en mots simples.
     */
    public static function answerFor(WorkOrder $workOrder): string
    {
        $tech = $workOrder->assignee?->name;
        $when = fn (CarbonInterface $at) => ($at->isToday() ? 'aujourd\'hui' : ($at->isTomorrow() ? 'demain' : 'le '.$at->format('d/m'))).' vers '.$at->format('H\hi');

        return match ($workOrder->status) {
            'ouvert' => $tech
                ? ($workOrder->scheduled_at?->isFuture() ? "Un technicien passera {$when($workOrder->scheduled_at)}."
                    : ($workOrder->acknowledged_at ? 'Le technicien a pris la demande en charge et va passer.' : 'Un technicien est désigné et va passer.'))
                : 'La maintenance est prévenue ; un technicien va être désigné.',
            'en_cours' => 'La réparation est en cours.',
            'en_attente' => 'La réparation est en pause (pièce à recevoir ou accès à la chambre).',
            'rejete' => 'La réparation est en cours de reprise.',
            'resolu', 'ferme' => 'C\'est réparé'.($workOrder->completed_at ? ' depuis '.$when($workOrder->completed_at) : '').'.',
            'annule' => 'Ce signalement a été annulé.',
            default => WorkOrder::STATUS_LABELS[$workOrder->status] ?? $workOrder->status,
        };
    }

    /**
     * Données propres à l'accueil de la réception (en plus du dashboard commun) :
     * chambre recherchée, chambres occupées touchées, astreinte, blocages à décider.
     *
     * @return array<string, mixed>
     */
    public static function dashboard(User $user, string $searched): array
    {
        $room = $searched !== '' ? Room::where('number', $searched)->orWhere('name', $searched)->first() : null;

        return [
            'searched' => $searched,
            'lookup' => $room ? self::roomOverview($room) : null,
            'roomNumbers' => Room::rooms()->orderBy('number')->pluck('number'),
            'guestRooms' => $guestRooms = self::guestRooms(),
            'onCall' => self::onCall(),
            'pendingBlocks' => self::pendingBlocks(),
            'kpis' => self::kpis($guestRooms),
            'chart' => RoomActivity::weekChart(),
            'activity' => RoomActivity::feed(),
            // Point de départ de l'actualisation automatique (ReceptionController::state).
            'signature' => self::signature($user),
            'unreadNow' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * Tout ce qu'il faut savoir sur une chambre pour répondre au client : pannes en
     * cours (tous services), blocage, situation du client, réparations récentes.
     *
     * @return array<string, mixed>
     */
    public static function roomOverview(Room $room): array
    {
        $open = WorkOrder::open()->where('room_id', $room->id)
            ->with('assignee', 'reporter', 'priority', 'type')->latest()->get();
        $recent = WorkOrder::where('room_id', $room->id)->whereIn('status', ['resolu', 'ferme'])
            ->where('completed_at', '>=', now()->subDays(self::HISTORY_DAYS))
            ->with('assignee')->latest('completed_at')->limit(5)->get();

        return [
            'room' => $room,
            'block' => $room->activeBlock(),
            'open' => $open,
            'recent' => $recent,
            'occupancy' => $open->pluck('room_occupancy')->filter()->first(),
            'deadline' => $open->pluck('due_date')->filter()->sort()->first(),
        ];
    }

    /**
     * Chambres où un client est concerné maintenant : il est dedans, il est sorti et
     * revient, ou une arrivée est attendue avant la réparation. Les plus pressées d'abord.
     *
     * @return Collection<int, array{room: Room, orders: Collection, occupancy: ?RoomOccupancy, deadline: ?Carbon, urgent: bool}>
     */
    public static function guestRooms(): Collection
    {
        return WorkOrder::open()->whereNotNull('room_id')
            ->where(fn ($q) => $q->whereIn('room_occupancy', [RoomOccupancy::ClientPresent, RoomOccupancy::ClientAbsent])->orWhereNotNull('due_date'))
            ->with('room', 'priority', 'assignee')->get()
            ->groupBy('room_id')
            ->map(function (Collection $orders) {
                $urgent = $orders->contains(fn (WorkOrder $w) => $w->priority?->code === 'urgente');

                return [
                    'room' => $orders->first()->room,
                    'orders' => $orders,
                    'occupancy' => $orders->pluck('room_occupancy')->filter()->first(),
                    'deadline' => $orders->pluck('due_date')->filter()->sort()->first(),
                    'urgent' => $urgent,
                ];
            })
            // Client dans la chambre avec une urgence d'abord, puis l'échéance la plus proche.
            ->sortBy(fn (array $r) => [
                $r['occupancy'] === RoomOccupancy::ClientPresent && $r['urgent'] ? 0 : 1,
                $r['deadline']?->timestamp ?? PHP_INT_MAX,
            ])
            ->values();
    }

    /**
     * Qui appeler maintenant pour une panne (équipe d'astreinte du moment).
     *
     * @return array{label: string, hours: string, people: Collection<int, User>}
     */
    public static function onCall(): array
    {
        $day = OnCall::isDayTime();

        return [
            'label' => $day ? 'Astreinte de jour' : 'Astreinte de nuit',
            'hours' => $day ? OnCall::dayStart().' – '.OnCall::dayEnd() : OnCall::dayEnd().' – '.OnCall::dayStart(),
            'people' => OnCall::recipients(),
        ];
    }

    /**
     * Les trois repères de l'accueil : clients concernés (dont urgents), demandes de la
     * réception en cours (dont celles du jour), réparations de la semaine face à la précédente.
     *
     * @return array<int, array{label: string, value: int, icon: string, trend: ?string, tone: string}>
     */
    public static function kpis(Collection $guestRooms): array
    {
        $fromReception = fn () => WorkOrder::whereHas('reporter', fn ($q) => $q->where('role', UserRole::Reception));
        $urgent = $guestRooms->where('urgent', true)->count();
        $todayNew = $fromReception()->open()->whereDate('created_at', today())->count();
        $repaired = fn (Carbon $from, Carbon $to) => WorkOrder::whereNotNull('room_id')
            ->whereIn('status', ['resolu', 'ferme'])->whereBetween('completed_at', [$from, $to])->count();
        $thisWeek = $repaired(now()->subDays(6)->startOfDay(), now());
        $diff = $thisWeek - $repaired(now()->subDays(13)->startOfDay(), now()->subDays(7)->endOfDay());

        return [
            ['label' => 'Clients concernés', 'value' => $guestRooms->count(), 'icon' => 'users',
                'trend' => $urgent ? $urgent.' urgent'.($urgent > 1 ? 's' : '') : null, 'tone' => 'down'],
            ['label' => 'Demandes en cours', 'value' => $fromReception()->open()->count(), 'icon' => 'clipboard',
                'trend' => $todayNew ? '+'.$todayNew.' aujourd\'hui' : null, 'tone' => 'neutral'],
            ['label' => 'Réparées en 7 jours', 'value' => $thisWeek, 'icon' => 'check',
                'trend' => $diff === 0 ? 'comme avant' : ($diff > 0 ? '+' : '').$diff.' vs 7 j. avant', 'tone' => $diff >= 0 ? 'up' : 'down'],
        ];
    }

    /** Inactivité après laquelle un poste du comptoir (partagé) déconnecte la réceptionniste. */
    public const IDLE_LOGOUT_MINUTES = 15;

    /**
     * Empreinte de ce que montre l'accueil de la réception : elle change dès qu'un client
     * devient concerné, qu'une échéance bouge, qu'un blocage ou une réparation à confirmer arrive.
     */
    public static function signature(User $user): string
    {
        $guests = self::guestRooms()->map(fn (array $g) => $g['room']->id.':'.$g['deadline']?->timestamp.':'.(int) $g['urgent'].':'.$g['orders']->pluck('id')->join(','));

        return md5(json_encode([
            $guests->all(),
            self::pendingBlocks()->pluck('id')->all(),
            WorkOrder::visibleTo($user)->awaitingRequesterConfirmation()->pluck('id')->all(),
        ]));
    }

    /** Demandes de blocage en attente de la décision de la réception. */
    public static function pendingBlocks(): Collection
    {
        return RoomBlock::where('status', RoomBlock::REQUESTED)->with('room', 'requester')->latest()->get();
    }

    /**
     * La réception déclare la situation du client pour une chambre en panne : elle
     * s'applique à tous les OT ouverts de la chambre (occupation, échéance), avec une
     * note visible de la maintenance ; le technicien affecté est prévenu.
     *
     * @return int nombre d'OT mis à jour
     */
    public static function updateSituation(Room $room, string $situation, ?string $time, ?string $otherRoom, ?string $note, User $by): int
    {
        $at = $time ? Carbon::createFromFormat('H:i', $time) : null;
        // Une heure déjà passée aujourd'hui désigne demain (retour ou arrivée tardive).
        if ($at && $at->isPast()) {
            $at->addDay();
        }

        [$occupancy, $deadline, $text] = match ($situation) {
            'present' => [RoomOccupancy::ClientPresent, null, 'le client est dans la chambre'],
            'sorti' => [RoomOccupancy::ClientAbsent, $at, 'le client est sorti, retour prévu '.self::moment($at)],
            'arrivee' => [RoomOccupancy::Libre, $at, 'chambre libre, arrivée d\'un client prévue '.self::moment($at).' : à réparer avant'],
            'reloge' => [RoomOccupancy::Libre, null, 'client relogé'.($otherRoom ? ' en '.$otherRoom : '').' : la chambre est libre, vous pouvez y entrer'],
            'libre' => [RoomOccupancy::Libre, null, 'chambre libre (client parti) : vous pouvez y entrer'],
        };
        $content = 'Réception ('.$by->name.') : '.$text.(filled($note) ? '. '.trim($note) : '.');

        $orders = WorkOrder::open()->where('room_id', $room->id)->with('assignee', 'room')->get();
        foreach ($orders as $workOrder) {
            $workOrder->forceFill([
                'room_occupancy' => $occupancy,
                'due_date' => $deadline,
                // Nouvelle échéance : l'alerte « le client revient » pourra repartir.
                'reception_alerted_at' => $deadline ? null : $workOrder->reception_alerted_at,
            ])->save();
            $workOrder->comments()->create(['user_id' => $by->id, 'content' => $content]);

            if ($workOrder->assignee && $workOrder->assignee->id !== $by->id) {
                $workOrder->assignee->notify(new GuestSituationNotification($workOrder, $content));
            }
        }

        return $orders->count();
    }

    private static function moment(?CarbonInterface $at): string
    {
        return $at ? ($at->isToday() ? 'à ' : 'demain à ').$at->format('H\hi') : '';
    }
}
