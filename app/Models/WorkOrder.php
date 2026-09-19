<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'room_id', 'equipment_id', 'maintenance_plan_id',
        'assigned_to', 'reported_by', 'type_id', 'priority_id', 'status',
        'due_date', 'started_at', 'completed_at',
        'scheduled_at', 'estimated_duration_minutes', 'scheduled_by',
        'sla_policy_id', 'sla_response_due_at', 'sla_resolution_due_at', 'sla_breached',
    ];

    protected $casts = [
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
    }

    // ===== Relations =====
    public function room()
    {
        return $this->belongsTo(Room::class);
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

    // ===== Scopes =====
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['ouvert', 'en_cours', 'en_attente']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['resolu', 'ferme']);
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
            ->whereNotIn('status', ['resolu', 'ferme']);
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
            UserRole::Housekeeping, UserRole::Reception => $query->where('reported_by', $user->id),
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

    // ===== Helpers d'affichage =====
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ouvert'     => 'Ouvert',
            'en_cours'   => 'En cours',
            'en_attente' => 'En attente',
            'resolu'     => 'Résolu',
            'ferme'      => 'Fermé',
            'rejete'     => 'Rejeté',
            default      => $this->status,
        };
    }

    public function getScheduledEndAtAttribute(): ?\Carbon\Carbon
    {
        if (is_null($this->scheduled_at) || is_null($this->estimated_duration_minutes)) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->estimated_duration_minutes);
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
}