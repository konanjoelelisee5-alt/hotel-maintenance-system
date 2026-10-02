<?php

namespace App\Models;

use App\Enums\RoomOccupancy;
use App\Enums\UserRole;
use App\Observers\NotifyRequestersOfProgress;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(NotifyRequestersOfProgress::class)]
class WorkOrder extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'room_id', 'equipment_id', 'maintenance_plan_id',
        'assigned_to', 'reported_by', 'type_id', 'priority_id', 'status',
        'due_date', 'started_at', 'completed_at',
        'scheduled_at', 'estimated_duration_minutes', 'scheduled_by',
        'sla_policy_id', 'sla_response_due_at', 'sla_resolution_due_at', 'sla_breached',
        'room_occupancy', 'reception_alerted_at',
    ];

    protected $casts = [
        'room_occupancy' => RoomOccupancy::class,
        'reception_alerted_at' => 'datetime',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sla_response_due_at' => 'datetime',
        'sla_resolution_due_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (WorkOrder $workOrder) {
            $workOrder->applySlaPolicy();
        });

        // Requalification : la priorité ou le type change le délai SLA. Un OT déjà
        // terminé garde son résultat d'origine (les rapports de conformité en dépendent).
        static::updated(function (WorkOrder $workOrder) {
            if ($workOrder->wasChanged(['priority_id', 'type_id'])
                && ! in_array($workOrder->status, self::FINISHED_STATUSES, true)) {
                $workOrder->recalculateSla();
            }
        });
    }

    // ===== Relations =====
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomBlocks()
    {
        return $this->hasMany(RoomBlock::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function maintenancePlan()
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function scheduledBy()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function type()
    {
        return $this->belongsTo(WorkOrderType::class, 'type_id');
    }

    public function priority()
    {
        return $this->belongsTo(WorkOrderPriority::class, 'priority_id');
    }

    public function comments()
    {
        return $this->hasMany(WorkOrderComment::class)->latest();
    }

    public function attachments()
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(WorkOrderStatusHistory::class)->latest();
    }

    public function interventionSessions()
    {
        return $this->hasMany(InterventionSession::class)->latest();
    }

    public function interventionReport()
    {
        return $this->hasOne(InterventionReport::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function qualityControls()
    {
        return $this->hasMany(WorkOrderQualityControl::class)->latest();
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class)->latest();
    }

    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    public function escalationLogs()
    {
        return $this->hasMany(EscalationLog::class);
    }

    public function partReservations()
    {
        return $this->hasMany(PartReservation::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Statuts où plus personne n'attend de réparation : ni retard, ni escalade SLA. */
    public const FINISHED_STATUSES = ['resolu', 'ferme', 'annule'];

    // ===== Scopes =====
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['ouvert', 'en_cours', 'en_attente']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', self::FINISHED_STATUSES);
    }

    public function scopeScheduledBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end]);
    }

    public function scopeSlaResolutionOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNotIn('status', self::FINISHED_STATUSES);
    }

    /**
     * En retard, encore à traiter : signalé dépassé, ou échéance passée sans attendre
     * que la tâche planifiée pose sla_breached. Même règle sur la Supervision et la liste.
     */
    public function scopeLate(Builder $query): Builder
    {
        return $query->open()->where(fn (Builder $q) => $q
            ->where('sla_breached', true)
            ->orWhere(fn (Builder $o) => $o->slaResolutionOverdue()));
    }

    public function scopePreventive(Builder $query): Builder
    {
        return $query->whereNotNull('maintenance_plan_id');
    }

    /**
     * Limite la requête aux OT que ce rôle a le droit de voir dans les listes/badges
     * (même règle que WorkOrderController::index / WorkOrderPolicy) : admin/manager
     * voient tout, technicien ses OT assignés, housekeeping/réception ceux qu'ils ont signalés.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Technicien => $query->where('assigned_to', $user->id),
            UserRole::Housekeeping, UserRole::Reception => $user->isDepartmentHead()
                // Responsable : ses signalements et ceux de tout son service.
                ? $query->where(fn (Builder $q) => $q
                    ->where('reported_by', $user->id)
                    ->orWhereHas('reporter', fn (Builder $r) => $r->where('role', $user->role)))
                : $query->where('reported_by', $user->id),
            default => $query,
        };
    }

    // ===== Logique métier =====
    public function activeSession()
    {
        return $this->interventionSessions()->whereNull('ended_at')->first();
    }

    public function getTotalWorkedMinutesAttribute(): int
    {
        return $this->interventionSessions()->whereNotNull('duration_minutes')->sum('duration_minutes');
    }

    public function latestQualityControl()
    {
        return $this->qualityControls()->first();
    }

    /**
     * Applique une politique SLA à cet OT : calcule et fige les dates limites
     * en fonction de la date de création de l'OT.
     */
    public function applySlaPolicy(): void
    {
        $policy = SlaPolicy::findBestMatch($this->priority->code, $this->type->code);

        if (! $policy) {
            return;
        }

        $this->update([
            'sla_policy_id' => $policy->id,
            'sla_response_due_at' => $this->created_at->copy()->addMinutes($policy->response_time_minutes),
            'sla_resolution_due_at' => $this->created_at->copy()->addMinutes($policy->resolution_time_minutes),
        ]);
    }

    /**
     * Après une requalification (priorité ou type) : la politique SLA est recherchée de
     * nouveau et les délais recalculés depuis la création de l'OT, comme à l'origine.
     * « Dépassé » reflète la nouvelle échéance (un OT passé d'Urgente à Basse peut ne
     * plus être en retard). Tracé au journal avec l'ancienne et la nouvelle échéance.
     */
    public function recalculateSla(): void
    {
        // Les relations chargées pointent encore vers l'ancienne priorité / l'ancien type.
        $this->unsetRelation('priority')->unsetRelation('type');

        $before = $this->sla_resolution_due_at?->copy();
        $policy = SlaPolicy::findBestMatch($this->priority->code, $this->type->code);
        $responseDue = $policy ? $this->created_at->copy()->addMinutes($policy->response_time_minutes) : null;
        $resolutionDue = $policy ? $this->created_at->copy()->addMinutes($policy->resolution_time_minutes) : null;

        $unchanged = $before && $resolutionDue ? $before->equalTo($resolutionDue) : ! $before && ! $resolutionDue;
        if ($unchanged) {
            return;
        }

        $this->update([
            'sla_policy_id' => $policy?->id,
            'sla_response_due_at' => $responseDue,
            'sla_resolution_due_at' => $resolutionDue,
            'sla_breached' => (bool) $resolutionDue?->isPast(),
        ]);

        $format = fn ($date) => $date ? $date->format('d/m/Y à H\hi') : 'aucune (pas de politique SLA)';
        ActivityLog::record(
            'work_order.sla_recalculated',
            "SLA de l'OT {$this->code()} recalculé (priorité {$this->priority->label}, type {$this->type->label}) : "
                ."résolution attendue {$format($before)} → {$format($resolutionDue)}",
            $this,
            [
                'policy' => $policy?->name,
                'resolution_due_at' => ['from' => $before?->toIso8601String(), 'to' => $resolutionDue?->toIso8601String()],
                'breached' => (bool) $resolutionDue?->isPast(),
            ],
        );
    }

    // ===== Helpers d'affichage =====
    /** Libellés des statuts, dans l'ordre du cycle de vie (listes, graphiques, PDF). */
    public const STATUS_LABELS = [
        'ouvert' => 'Ouvert',
        'en_cours' => 'En cours',
        'en_attente' => 'En attente',
        'resolu' => 'Résolu',
        'rejete' => 'Rejeté',
        'ferme' => 'Fermé',
        'annule' => 'Annulé',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getScheduledEndAtAttribute(): ?\Carbon\Carbon
    {
        if (is_null($this->scheduled_at) || is_null($this->estimated_duration_minutes)) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->estimated_duration_minutes);
    }

    /**
     * Cette personne a-t-elle travaillé sur l'OT (assignée, chrono ou rapport) ?
     * Sert à l'empêcher d'en faire aussi le contrôle qualité.
     */
    public function wasExecutedBy(User $user): bool
    {
        return $this->assigned_to === $user->id
            || $this->interventionReport()->where('technician_id', $user->id)->exists()
            || $this->interventionSessions()->where('technician_id', $user->id)->exists();
    }

    /**
     * Référence courte affichée dans les listes/fiches (ex. "OT-00123").
     */
    public function code(): string
    {
        return 'OT-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Clé de couleur sémantique (App\Support\Swatch) selon l'état du SLA.
     */
    public function slaColorClass(): string
    {
        if (is_null($this->sla_resolution_due_at)) {
            return 'grey';
        }

        if ($this->sla_breached) {
            return 'red';
        }

        return $this->slaProgressPercent() >= 75 ? 'amber' : 'green';
    }

    /**
     * Pourcentage du délai SLA de résolution déjà écoulé depuis la création (0-100).
     */
    public function slaProgressPercent(): int
    {
        if (is_null($this->sla_resolution_due_at)) {
            return 0;
        }

        if ($this->sla_breached) {
            return 100;
        }

        $total = $this->created_at->diffInMinutes($this->sla_resolution_due_at);
        $elapsed = $this->created_at->diffInMinutes(now());

        return $total > 0 ? (int) min(100, max(0, round($elapsed / $total * 100))) : 100;
    }

    /**
     * Libellé court du temps restant/dépassé avant l'échéance SLA de résolution.
     */
    public function slaRemainingLabel(): string
    {
        if (is_null($this->sla_resolution_due_at)) {
            return 'Pas de SLA';
        }

        if ($this->sla_breached || $this->sla_resolution_due_at->isPast()) {
            return 'Dépassé de '.$this->sla_resolution_due_at->locale('fr')->diffForHumans(null, true);
        }

        return $this->sla_resolution_due_at->locale('fr')->diffForHumans(null, true).' restantes';
    }

    /**
     * État SLA prêt à afficher dans les listes : un OT en cours a un compte à rebours,
     * un OT terminé n'en a plus — on dit seulement s'il a tenu son délai.
     *
     * @return array{text: string, color: string, width: int, late: bool}
     */
    public function slaSummary(): array
    {
        $due = $this->sla_resolution_due_at;

        if (in_array($this->status, self::FINISHED_STATUSES, true)) {
            return match (true) {
                ! $due || $this->status === 'annule' => ['text' => '—', 'color' => 'grey', 'width' => 0, 'late' => false],
                $this->sla_breached || (bool) $this->completed_at?->greaterThan($due)
                    => ['text' => 'Terminé hors délai', 'color' => 'red', 'width' => 0, 'late' => false],
                default => ['text' => 'Terminé dans le délai', 'color' => 'green', 'width' => 0, 'late' => false],
            };
        }

        $late = $this->sla_breached || (bool) $due?->isPast();

        return [
            'text' => $this->slaRemainingLabel(),
            'color' => $late ? 'red' : $this->slaColorClass(),
            'width' => $late ? 100 : $this->slaProgressPercent(),
            'late' => $late,
        ];
    }
}