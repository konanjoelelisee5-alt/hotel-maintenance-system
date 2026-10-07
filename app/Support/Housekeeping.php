<?php

namespace App\Support;

use App\Enums\IssueCategory;
use App\Enums\RoomOccupancy;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Vocabulaire des écrans Housekeeping : l'agent ne suit que trois états (en attente
 * d'un technicien, en cours, réparé) là où la maintenance en gère sept. Le
 * regroupement ne vaut que pour l'affichage HK ; les autres rôles gardent les
 * statuts et couleurs de WorkOrder::STATUS_COLORS.
 */
class Housekeeping
{
    /**
     * Groupe d'affichage => statuts réels qu'il couvre. « rejete » (contrôle qualité
     * refusé, le technicien reprend) est une réparation en cours pour l'agent :
     * affiché « Rejeté », il croirait que son signalement a été refusé.
     */
    public const GROUPS = [
        'pending' => ['ouvert'],
        'progress' => ['en_cours', 'en_attente', 'rejete'],
        'done' => ['resolu', 'ferme'],
    ];

    /** Signalements terminés : l'« Historique » de la liste. */
    public const FINISHED = ['resolu', 'ferme', 'annule'];

    /** Délai pendant lequel l'agent peut retirer un signalement fait par erreur. */
    public const WITHDRAW_WINDOW_MINUTES = 15;

    /** Motifs proposés pour retirer un signalement (l'historique de l'OT les cite). */
    public const WITHDRAW_REASONS = [
        'lieu' => 'Erreur de chambre ou de lieu',
        'doublon' => 'Déjà signalé',
        'regle' => 'Le problème est réglé',
        'erreur' => 'Signalement envoyé par erreur',
    ];

    /** Fenêtre de détection des pannes qui reviennent dans une même chambre. */
    public const REPEAT_DAYS = 30;

    /** Classes complètes (lues par Tailwind) : texte, fond, point. */
    private const STYLES = [
        'pending' => ['label' => 'En attente', 'text' => 'text-hk-pending', 'bg' => 'bg-hk-pending-bg', 'dot' => 'bg-hk-pending'],
        'progress' => ['label' => 'En cours', 'text' => 'text-hk-progress', 'bg' => 'bg-hk-progress-bg', 'dot' => 'bg-hk-progress'],
        'done' => ['label' => 'Réparé', 'text' => 'text-hk-done', 'bg' => 'bg-hk-done-bg', 'dot' => 'bg-hk-done'],
        'rejete' => ['label' => 'Rejeté', 'text' => 'text-ink-soft', 'bg' => 'bg-sand', 'dot' => 'bg-ink-label'],
        'annule' => ['label' => 'Annulé', 'text' => 'text-ink-soft', 'bg' => 'bg-sand', 'dot' => 'bg-ink-label'],
    ];

    public static function group(?string $status): string
    {
        foreach (self::GROUPS as $group => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $group;
            }
        }

        return $status === 'annule' ? 'annule' : 'rejete';
    }

    /** @return array{key: string, label: string, text: string, bg: string, dot: string} */
    public static function status(?string $status): array
    {
        $group = self::group($status);

        return ['key' => $group] + self::STYLES[$group];
    }

    /**
     * Catégorie du signalement rapide, retrouvée dans le titre de l'OT
     * (« Climatisation · Chambre 214 ») : elle n'a pas de colonne à elle.
     */
    public static function category(WorkOrder $workOrder): ?IssueCategory
    {
        foreach (IssueCategory::cases() as $category) {
            if (str_starts_with($workOrder->title, $category->label().' · ')) {
                return $category;
            }
        }

        return null;
    }

    public static function categoryLabel(IssueCategory $category): string
    {
        return match ($category) {
            IssueCategory::Eau => 'Eau',
            IssueCategory::Electricite => 'Électricité',
            IssueCategory::Clim => 'Climatisation',
            IssueCategory::Tv => 'TV',
            IssueCategory::Mobilier => 'Mobilier',
            IssueCategory::Porte => 'Porte',
            IssueCategory::Autre => 'Autre',
        };
    }

    /** Icône (x-hk.icon) de la catégorie. */
    public static function categoryIcon(?IssueCategory $category): string
    {
        return match ($category) {
            IssueCategory::Eau => 'droplet',
            IssueCategory::Electricite => 'zap',
            IssueCategory::Clim => 'snowflake',
            IssueCategory::Tv => 'tv',
            IssueCategory::Mobilier => 'armchair',
            IssueCategory::Porte => 'key',
            IssueCategory::Autre => 'help',
            default => 'wrench',
        };
    }

    public static function occupancyIcon(RoomOccupancy $occupancy): string
    {
        return match ($occupancy) {
            RoomOccupancy::Libre => 'calendar-check',
            RoomOccupancy::ClientAbsent => 'briefcase',
            RoomOccupancy::ClientPresent => 'user',
            RoomOccupancy::Depart => 'log-out',
        };
    }

    /**
     * Frise de suivi de la fiche : Signalé → Technicien affecté → En cours → Réparé.
     *
     * @return array<int, array{label: string, done: bool, at: ?Carbon, meta: ?string}>
     */
    public static function timeline(WorkOrder $workOrder): array
    {
        $repaired = in_array($workOrder->status, self::GROUPS['done'], true);
        $started = $repaired || in_array($workOrder->status, self::GROUPS['progress'], true);

        return [
            ['label' => 'Signalé', 'done' => true, 'at' => $workOrder->created_at, 'meta' => $workOrder->reporter?->name],
            // L'heure d'affectation n'est pas enregistrée : on montre le passage prévu.
            ['label' => 'Technicien affecté', 'done' => $workOrder->assigned_to !== null, 'at' => null,
                'meta' => $workOrder->assignee ? $workOrder->assignee->name.($workOrder->scheduled_at ? ' · passage prévu '.$workOrder->scheduled_at->format('d/m H\hi') : '') : null],
            ['label' => 'En cours', 'done' => $started, 'at' => $workOrder->started_at, 'meta' => null],
            ['label' => 'Réparé', 'done' => $repaired, 'at' => $workOrder->completed_at, 'meta' => null],
        ];
    }

    /**
     * Signalements encore ouverts, par lieu, pour prévenir les doublons pendant la
     * saisie. Clé de lieu identique à celle du formulaire : numéro de chambre, ou
     * « area:{id} » pour un espace commun. Tout l'hôtel (deux agents peuvent signaler
     * la même panne), mais seule l'auteure reçoit le lien vers la fiche.
     *
     * @return array<int, array{place: string, category: ?string, label: string, code: string, ago: string, assigned: bool, url: ?string}>
     */
    public static function openReportsByPlace(User $user): array
    {
        return WorkOrder::open()->whereNotNull('room_id')->with('room', 'assignee')->latest()->get()
            ->filter(fn (WorkOrder $w) => $w->room !== null)
            ->map(function (WorkOrder $w) use ($user) {
                $category = self::category($w);

                return [
                    'place' => $w->room->isCommonArea() ? 'area:'.$w->room_id : (string) $w->room->number,
                    'category' => $category?->value,
                    'label' => $category ? self::categoryLabel($category) : $w->title,
                    'code' => $w->code(),
                    'ago' => $w->created_at->locale('fr')->diffForHumans(),
                    'assigned' => $w->assigned_to !== null,
                    // « Compléter » : seulement si l'agent peut ajouter une précision à cet OT.
                    'url' => $user->can('complement', $w) ? route('work-orders.show', $w).'#completer' : null,
                ];
            })
            ->values()->all();
    }

    /** Nombre de signalements de même catégorie dans la même chambre sur REPEAT_DAYS jours (celui-ci compris). */
    public static function repeatCount(WorkOrder $workOrder): int
    {
        $category = self::category($workOrder);
        if (! $category || ! $workOrder->room_id) {
            return 0;
        }

        return WorkOrder::where('room_id', $workOrder->room_id)
            ->where('title', 'like', $category->label().' · %')
            ->where('created_at', '>=', $workOrder->created_at->copy()->subDays(self::REPEAT_DAYS))
            ->where('created_at', '<=', $workOrder->created_at)
            ->whereNot('status', 'annule')
            ->count();
    }

    /**
     * Pannes qui reviennent (gouvernante) : même chambre, même catégorie, au moins deux
     * fois en REPEAT_DAYS jours, parmi les signalements que l'utilisateur peut voir.
     *
     * @return Collection<int, array{place: string, category: string, icon: string, count: int, last: WorkOrder}>
     */
    public static function repeats(User $user, int $limit = 5): Collection
    {
        return WorkOrder::visibleTo($user)
            ->whereNotNull('room_id')
            ->whereNot('status', 'annule')
            ->where('created_at', '>=', now()->subDays(self::REPEAT_DAYS))
            ->with('room')->latest()->get()
            ->filter(fn (WorkOrder $w) => $w->room && self::category($w))
            ->groupBy(fn (WorkOrder $w) => $w->room_id.'|'.self::category($w)->value)
            ->filter(fn (Collection $group) => $group->count() >= 2)
            ->map(fn (Collection $group) => [
                'place' => $group->first()->room->label,
                'category' => self::categoryLabel(self::category($group->first())),
                'icon' => self::categoryIcon(self::category($group->first())),
                'count' => $group->count(),
                'last' => $group->first(),
            ])
            ->sortByDesc('count')->take($limit)->values();
    }

    /**
     * Outils de la gouvernante : menu (sidebar), rail de la tablette et accueil sur téléphone.
     *
     * @return array<int, array{label: string, route: string, icon: string, hk: string, description: string}>
     */
    public static function headTools(): array
    {
        return [
            // 'match' exact : « housekeeping.* » allumerait aussi l'Accueil et les autres outils.
            ['label' => 'Plan des étages', 'route' => 'housekeeping.floor-plan', 'match' => ['housekeeping.floor-plan'], 'icon' => 'pin', 'hk' => 'building', 'description' => 'L\'état de chaque chambre d\'un coup d\'œil'],
            ['label' => 'Inspections', 'route' => 'inspections.index', 'icon' => 'shield', 'hk' => 'check-circle', 'description' => 'Tournée d\'inspection des chambres'],
            ['label' => 'Bilan du mois', 'route' => 'housekeeping.monthly-report', 'match' => ['housekeeping.monthly-report'], 'icon' => 'report', 'hk' => 'activity', 'description' => 'Pannes, délais de réparation, chambres touchées'],
        ];
    }

    /**
     * Gouvernante : signalements encore ouverts par agent de son équipe, les plus chargés d'abord.
     *
     * @return Collection<int, array{user: ?User, count: int}>
     */
    public static function teamLoad(User $head, int $limit = 5): Collection
    {
        return WorkOrder::visibleTo($head)->open()->with('reporter')->get()
            ->groupBy('reported_by')
            ->map(fn (Collection $orders) => ['user' => $orders->first()->reporter, 'count' => $orders->count()])
            ->sortByDesc('count')->take($limit)->values();
    }

    /** Gouvernantes actives (responsables du service Housekeeping). */
    public static function heads(): Collection
    {
        return User::where('role', UserRole::Housekeeping)->where('is_department_head', true)->where('is_active', true)->get();
    }

    /** Titre de groupe de l'historique : « Aujourd'hui », « Hier », « Lundi 29 septembre ». */
    public static function dayLabel(CarbonInterface $date): string
    {
        return match (true) {
            $date->isToday() => "Aujourd'hui",
            $date->isYesterday() => 'Hier',
            default => ucfirst($date->locale('fr')->translatedFormat($date->isCurrentYear() ? 'l j F' : 'l j F Y')),
        };
    }
}
