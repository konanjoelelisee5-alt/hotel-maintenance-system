<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'room_id', 'equipment_id',
        'work_order_type_id', 'work_order_priority_id', 'checklist_template_id',
        'estimated_duration_minutes', 'assigned_to', 'skill_id',
        'frequency_unit', 'frequency_interval', 'lead_time_days',
        'start_date', 'end_date', 'next_due_at', 'last_generated_at',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_at' => 'date',
        'last_generated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Au premier enregistrement, la première échéance est la date de départ du plan.
        static::creating(function (MaintenancePlan $plan) {
            if (is_null($plan->next_due_at) && $plan->start_date) {
                $plan->next_due_at = $plan->start_date;
            }
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

    public function type()
    {
        return $this->belongsTo(WorkOrderType::class, 'work_order_type_id');
    }

    public function priority()
    {
        return $this->belongsTo(WorkOrderPriority::class, 'work_order_priority_id');
    }

    public function checklistTemplate()
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requiredSkill()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class)->latest();
    }

    // ===== Scopes =====
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Restreint (grossièrement) aux plans potentiellement dus, pour limiter la volumétrie
     * chargée par la commande de génération. Le filtrage précis (lead time inclus) se fait
     * ensuite en PHP via isDueForGeneration(), car le délai de préavis varie par plan.
     */
    public function scopeCandidateForGeneration(Builder $query, ?Carbon $today = null): Builder
    {
        $today = $today ?? today();

        return $query->active()
            ->whereDate('start_date', '<=', $today)
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->whereNotNull('next_due_at');
    }

    // ===== Logique d'automatisation =====

    /**
     * Ce plan doit-il générer un nouvel OT aujourd'hui, en tenant compte
     * du délai de préavis (lead_time_days) ?
     */
    public function isDueForGeneration(?Carbon $today = null): bool
    {
        $today = $today ?? today();

        if (! $this->is_active || is_null($this->next_due_at)) {
            return false;
        }

        if ($this->start_date && $this->start_date->gt($today)) {
            return false;
        }

        if ($this->end_date && $this->end_date->lt($today)) {
            return false;
        }

        return $this->next_due_at->copy()->subDays($this->lead_time_days)->lte($today);
    }

    /**
     * Calcule la prochaine échéance à partir d'une date donnée, selon la fréquence du plan.
     */
    public function computeNextOccurrence(Carbon $from): Carbon
    {
        return match ($this->frequency_unit) {
            'jour' => $from->copy()->addDays($this->frequency_interval),
            'semaine' => $from->copy()->addWeeks($this->frequency_interval),
            'mois' => $from->copy()->addMonthsNoOverflow($this->frequency_interval),
            'trimestre' => $from->copy()->addMonthsNoOverflow($this->frequency_interval * 3),
            'annee' => $from->copy()->addYears($this->frequency_interval),
            default => $from->copy()->addMonthsNoOverflow($this->frequency_interval),
        };
    }

    /**
     * Détermine automatiquement le technicien à assigner à l'OT généré :
     * 1. le technicien fixe du plan s'il est actif ;
     * 2. sinon, parmi les techniciens possédant la compétence requise et non en congé/absence
     *    à la date d'échéance, celui qui a la charge de travail ouverte la plus faible ;
     * 3. sinon, aucun (l'OT reste non assigné, à répartir manuellement lors de la planification).
     *
     * Note : la disponibilité est vérifiée par jour (congé/absence), pas par créneau horaire —
     * l'échéance d'un plan n'a pas d'heure fixe, celle-ci est fixée plus tard via le planning.
     */
    public function pickAssignee(): ?User
    {
        if ($this->assigned_to && $this->assignee?->is_active) {
            return $this->assignee;
        }

        if (! $this->skill_id) {
            return null;
        }

        return User::query()
            ->where('role', 'technicien')
            ->where('is_active', true)
            ->whereHas('skills', fn (Builder $q) => $q->where('skills.id', $this->skill_id))
            ->withCount(['assignedWorkOrders as open_count' => fn (Builder $q) => $q->open()])
            ->get()
            ->reject(fn (User $technician) => $this->next_due_at && $technician->isOnLeaveOn($this->next_due_at))
            ->sortBy('open_count')
            ->first();
    }

    /**
     * Génère l'ordre de travail dû, fait avancer l'échéance du plan à la prochaine
     * occurrence, et retourne l'OT créé. Idempotent au sein d'une même journée :
     * une fois generateWorkOrder() appelé, isDueForGeneration() redevient false
     * jusqu'à la prochaine échéance.
     */
    public function generateWorkOrder(): WorkOrder
    {
        $assignee = $this->pickAssignee();
        $dueAt = $this->next_due_at->copy();

        $workOrder = WorkOrder::create([
            'title' => "Maintenance préventive — {$this->name}",
            'description' => $this->description,
            'room_id' => $this->room_id,
            'equipment_id' => $this->equipment_id,
            'assigned_to' => $assignee?->id,
            'reported_by' => $this->created_by,
            'type_id' => $this->work_order_type_id,
            'priority_id' => $this->work_order_priority_id,
            'status' => 'ouvert',
            'due_date' => $dueAt,
            'scheduled_at' => $assignee ? $dueAt : null,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'maintenance_plan_id' => $this->id,
        ]);

        $workOrder->statusHistories()->create([
            'changed_by' => $this->created_by,
            'old_status' => null,
            'new_status' => 'ouvert',
            'note' => "Généré automatiquement par le plan de maintenance préventive « {$this->name} ».",
        ]);

        if ($assignee) {
            $assignee->notify(new \App\Notifications\PreventiveWorkOrderGeneratedNotification($workOrder));
        }

        $this->update([
            'last_generated_at' => now(),
            'next_due_at' => $this->computeNextOccurrence($dueAt),
        ]);

        return $workOrder;
    }

    // ===== Helpers d'affichage =====
    public function getFrequencyLabelAttribute(): string
    {
        $unit = match ($this->frequency_unit) {
            'jour' => $this->frequency_interval > 1 ? 'jours' : 'jour',
            'semaine' => $this->frequency_interval > 1 ? 'semaines' : 'semaine',
            'mois' => 'mois',
            'trimestre' => $this->frequency_interval > 1 ? 'trimestres' : 'trimestre',
            'annee' => $this->frequency_interval > 1 ? 'ans' : 'an',
            default => $this->frequency_unit,
        };

        return "Tous les {$this->frequency_interval} {$unit}";
    }
}
