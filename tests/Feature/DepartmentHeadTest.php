<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentHeadTest extends TestCase
{
    use RefreshDatabase;

    private function reportedBy(User $reporter, string $title = 'Fuite robinet'): WorkOrder
    {
        return WorkOrder::create([
            'title' => $title,
            'reported_by' => $reporter->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
    }

    // ===== Visibilité =====

    public function test_head_sees_work_orders_reported_by_their_team(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $agent = User::factory()->housekeeping()->create();
        $workOrder = $this->reportedBy($agent);

        $this->assertTrue(WorkOrder::visibleTo($head)->whereKey($workOrder->id)->exists());
        $this->actingAs($head)->get(route('work-orders.show', $workOrder))->assertOk();
    }

    public function test_head_does_not_see_other_departments(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $workOrder = $this->reportedBy(User::factory()->reception()->create());

        $this->assertFalse(WorkOrder::visibleTo($head)->whereKey($workOrder->id)->exists());
        $this->actingAs($head)->get(route('work-orders.show', $workOrder))->assertForbidden();
    }

    public function test_regular_agent_still_sees_only_own_reports(): void
    {
        $agent = User::factory()->housekeeping()->create();
        $colleague = User::factory()->housekeeping()->create();
        $workOrder = $this->reportedBy($colleague);

        $this->assertFalse(WorkOrder::visibleTo($agent)->whereKey($workOrder->id)->exists());
        $this->actingAs($agent)->get(route('work-orders.show', $workOrder))->assertForbidden();
    }

    public function test_head_cannot_edit_team_work_orders(): void
    {
        $head = User::factory()->reception()->create(['is_department_head' => true]);
        $workOrder = $this->reportedBy(User::factory()->reception()->create());

        $this->actingAs($head)->get(route('work-orders.edit', $workOrder))->assertForbidden();
        $this->actingAs($head)->patch(route('work-orders.status.update', $workOrder), ['status' => 'ferme'])->assertForbidden();
    }

    public function test_head_has_no_access_to_configuration(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);

        $this->actingAs($head)->get(route('sla-policies.index'))->assertForbidden();
        $this->actingAs($head)->get(route('users.index'))->assertForbidden();
    }

    public function test_head_mine_filter_excludes_team_reports(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->reportedBy($head, 'Signalé par la responsable');
        $this->reportedBy(User::factory()->housekeeping()->create(), "Signalé par l'agent");

        $this->actingAs($head)->get(route('work-orders.index', ['filter' => 'mine']))
            ->assertSee('Signalé par la responsable')
            ->assertDontSee("Signalé par l&#039;agent", false);

        $this->actingAs($head)->get(route('work-orders.index', ['filter' => 'all']))
            ->assertSee("Signalé par l&#039;agent", false);
    }

    public function test_head_dashboard_renders(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true, 'name' => 'Responsable HK']);
        $this->reportedBy(User::factory()->housekeeping()->create(['name' => 'Agent Yao']));

        $this->actingAs($head)->get(route('housekeeping.dashboard'))
            ->assertOk()
            ->assertSee("Signalements de l&#039;équipe", false)
            ->assertSee('Agent Yao');
    }

    // ===== Gestion par l'admin =====

    public function test_flag_is_ignored_for_roles_without_department_head(): void
    {
        $technician = User::factory()->technicien()->create(['is_department_head' => true]);

        $this->assertFalse($technician->isDepartmentHead());
    }

    public function test_admin_can_designate_a_head_and_it_is_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->housekeeping()->create();

        $this->actingAs($admin)->put(route('users.update', $agent), [
            'name' => $agent->name,
            'email' => $agent->email,
            'role' => 'housekeeping',
            'is_department_head' => '1',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($agent->fresh()->isDepartmentHead());
        $this->assertTrue(ActivityLog::where('action', 'user.department_head_changed')->exists());
    }

    public function test_head_flag_is_cleared_when_role_changes_to_one_without_head(): void
    {
        $admin = User::factory()->admin()->create();
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);

        $this->actingAs($admin)->put(route('users.update', $head), [
            'name' => $head->name,
            'email' => $head->email,
            'role' => 'manager',
            'is_department_head' => '1',
            'is_active' => '1',
        ]);

        $this->assertFalse($head->fresh()->is_department_head);
    }
}
