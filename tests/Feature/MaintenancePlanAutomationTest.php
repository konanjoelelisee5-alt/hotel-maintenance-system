<?php

namespace Tests\Feature;

use App\Models\MaintenancePlan;
use App\Models\Room;
use App\Models\Skill;
use App\Models\TechnicianAvailability;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenancePlanAutomationTest extends TestCase
{
    use RefreshDatabase;

    private WorkOrderType $type;
    private WorkOrderPriority $priority;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Les types d'OT et priorités de base sont déjà semés par leurs migrations respectives.
        $this->type = WorkOrderType::where('code', 'preventif')->firstOrFail();
        $this->priority = WorkOrderPriority::where('code', 'moyenne')->firstOrFail();
        $this->admin = User::factory()->admin()->create();
    }

    private function makePlan(array $overrides = []): MaintenancePlan
    {
        return MaintenancePlan::create(array_merge([
            'name' => 'Contrôle mensuel climatisation',
            'room_id' => Room::factory()->create()->id,
            'work_order_type_id' => $this->type->id,
            'work_order_priority_id' => $this->priority->id,
            'frequency_unit' => 'mois',
            'frequency_interval' => 1,
            'lead_time_days' => 0,
            'start_date' => today()->subMonth(),
            'is_active' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_next_due_at_defaults_to_start_date_on_creation(): void
    {
        $plan = $this->makePlan(['start_date' => '2026-01-15']);

        $this->assertTrue($plan->next_due_at->isSameDay(Carbon::parse('2026-01-15')));
    }

    public function test_compute_next_occurrence_respects_frequency_without_month_overflow(): void
    {
        $plan = $this->makePlan(['frequency_unit' => 'mois', 'frequency_interval' => 1]);

        // 31 janvier + 1 mois ne doit pas déborder sur avril, mais s'aligner sur le dernier jour de février.
        $next = $plan->computeNextOccurrence(Carbon::parse('2026-01-31'));

        $this->assertTrue($next->isSameDay(Carbon::parse('2026-02-28')));
    }

    public function test_plan_is_due_only_within_lead_time_window(): void
    {
        $plan = $this->makePlan(['lead_time_days' => 3, 'next_due_at' => today()->addDays(5)]);
        $this->assertFalse($plan->isDueForGeneration());

        $plan->update(['next_due_at' => today()->addDays(2)]);
        $this->assertTrue($plan->fresh()->isDueForGeneration());
    }

    public function test_inactive_plan_is_never_due(): void
    {
        $plan = $this->makePlan(['is_active' => false, 'next_due_at' => today()]);

        $this->assertFalse($plan->isDueForGeneration());
    }

    public function test_plan_outside_its_start_end_window_is_not_due(): void
    {
        $futurePlan = $this->makePlan(['start_date' => today()->addWeek(), 'next_due_at' => today()->addWeek()]);
        $this->assertFalse($futurePlan->isDueForGeneration());

        $expiredPlan = $this->makePlan(['end_date' => today()->subDay(), 'next_due_at' => today()]);
        $this->assertFalse($expiredPlan->isDueForGeneration());
    }

    public function test_generate_work_order_creates_ot_and_advances_next_due_date(): void
    {
        $plan = $this->makePlan(['next_due_at' => today(), 'frequency_unit' => 'mois', 'frequency_interval' => 1]);

        $workOrder = $plan->generateWorkOrder();

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'maintenance_plan_id' => $plan->id,
            'type_id' => $this->type->id,
            'priority_id' => $this->priority->id,
            'status' => 'ouvert',
        ]);

        $plan->refresh();
        $this->assertTrue($plan->next_due_at->isSameDay(today()->addMonthNoOverflow()));
        $this->assertNotNull($plan->last_generated_at);

        // Un OT idempotent : le plan n'est plus dû tant que sa nouvelle échéance n'est pas atteinte.
        $this->assertFalse($plan->isDueForGeneration());
    }

    public function test_generate_work_order_records_status_history(): void
    {
        $plan = $this->makePlan(['next_due_at' => today()]);
        $workOrder = $plan->generateWorkOrder();

        $this->assertDatabaseHas('work_order_status_histories', [
            'work_order_id' => $workOrder->id,
            'new_status' => 'ouvert',
        ]);
    }

    public function test_pick_assignee_prefers_fixed_technician_over_skill(): void
    {
        $fixedTech = User::factory()->technicien()->create();
        $skill = Skill::create(['name' => 'Climatisation']);
        $otherTech = User::factory()->technicien()->create();
        $otherTech->skills()->attach($skill);

        $plan = $this->makePlan(['assigned_to' => $fixedTech->id, 'skill_id' => $skill->id]);

        $this->assertTrue($plan->pickAssignee()->is($fixedTech));
    }

    public function test_pick_assignee_auto_selects_least_loaded_technician_with_required_skill(): void
    {
        $skill = Skill::create(['name' => 'Climatisation']);

        $busyTech = User::factory()->technicien()->create();
        $busyTech->skills()->attach($skill);
        WorkOrder::factory()->open()->count(3)->create(['assigned_to' => $busyTech->id]);

        $freeTech = User::factory()->technicien()->create();
        $freeTech->skills()->attach($skill);

        // Ne possède pas la compétence requise : ne doit jamais être choisi.
        User::factory()->technicien()->create();

        $plan = $this->makePlan(['skill_id' => $skill->id]);

        $this->assertTrue($plan->pickAssignee()->is($freeTech));
    }

    public function test_pick_assignee_skips_technician_on_leave_at_due_date(): void
    {
        $skill = Skill::create(['name' => 'Climatisation']);
        $dueDate = today()->addDays(2);

        $onLeaveTech = User::factory()->technicien()->create();
        $onLeaveTech->skills()->attach($skill);
        TechnicianAvailability::create([
            'user_id' => $onLeaveTech->id,
            'type' => 'conge',
            'date_start' => $dueDate->copy()->subDay(),
            'date_end' => $dueDate->copy()->addDay(),
        ]);

        $availableTech = User::factory()->technicien()->create();
        $availableTech->skills()->attach($skill);

        $plan = $this->makePlan(['skill_id' => $skill->id, 'next_due_at' => $dueDate]);

        $this->assertTrue($plan->pickAssignee()->is($availableTech));
    }

    public function test_console_command_generates_only_due_plans(): void
    {
        $duePlan = $this->makePlan(['name' => 'Plan dû', 'next_due_at' => today()]);
        $futurePlan = $this->makePlan(['name' => 'Plan futur', 'next_due_at' => today()->addMonths(2)]);

        $this->artisan('maintenance:generate-preventive-work-orders')->assertExitCode(0);

        $this->assertDatabaseHas('work_orders', ['maintenance_plan_id' => $duePlan->id]);
        $this->assertDatabaseMissing('work_orders', ['maintenance_plan_id' => $futurePlan->id]);
    }

    public function test_console_command_is_idempotent_within_the_same_day(): void
    {
        $this->makePlan(['name' => 'Plan dû', 'next_due_at' => today()]);

        $this->artisan('maintenance:generate-preventive-work-orders')->assertExitCode(0);
        $this->artisan('maintenance:generate-preventive-work-orders')->assertExitCode(0);

        $this->assertSame(1, WorkOrder::preventive()->count());
    }
}
