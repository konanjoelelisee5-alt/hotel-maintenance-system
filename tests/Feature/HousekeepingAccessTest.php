<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousekeepingAccessTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Fuite sous le lavabo',
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            ...$overrides,
        ];
    }

    private function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Climatisation bruyante',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    // ===== Planning =====

    public function test_requesting_services_cannot_access_planning(): void
    {
        foreach ([User::factory()->housekeeping()->create(), User::factory()->reception()->create()] as $user) {
            $this->actingAs($user)->get(route('planning.index'))->assertForbidden();
            $this->actingAs($user)->get(route('planning.events'))->assertForbidden();
        }
    }

    public function test_technician_and_manager_keep_planning_access(): void
    {
        $technician = User::factory()->technicien()->create();

        $this->actingAs($technician)->get(route('planning.technician', $technician))->assertOk();
        $this->actingAs(User::factory()->manager()->create())->get(route('planning.index'))->assertOk();
    }

    // ===== Dispatch à la création =====

    public function test_housekeeping_cannot_assign_or_set_due_date_on_creation(): void
    {
        $agent = User::factory()->housekeeping()->create();
        $technician = User::factory()->technicien()->create();

        $this->actingAs($agent)->post(route('work-orders.store'), $this->payload([
            'assigned_to' => $technician->id,
            'due_date' => now()->addDay()->format('Y-m-d H:i'),
        ]))->assertRedirect();

        $workOrder = WorkOrder::where('reported_by', $agent->id)->firstOrFail();
        $this->assertNull($workOrder->assigned_to);
        $this->assertNull($workOrder->due_date);
    }

    public function test_housekeeping_create_form_hides_dispatch_fields(): void
    {
        $this->actingAs(User::factory()->housekeeping()->create())
            ->get(route('work-orders.create'))
            ->assertOk()
            ->assertDontSee('name="assigned_to"', false);
    }

    public function test_manager_can_still_assign_on_creation(): void
    {
        $technician = User::factory()->technicien()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->post(route('work-orders.store'), $this->payload(['assigned_to' => $technician->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('work_orders', ['title' => 'Fuite sous le lavabo', 'assigned_to' => $technician->id]);
    }

    // ===== Fermeture =====

    public function test_technician_can_resolve_but_not_close(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->workOrder(['assigned_to' => $technician->id]);

        $this->actingAs($technician)
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'ferme'])
            ->assertSessionHasErrors('status');
        $this->assertSame('ouvert', $workOrder->fresh()->status);

        $this->actingAs($technician)
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'resolu'])
            ->assertSessionHasNoErrors();
        $this->assertSame('resolu', $workOrder->fresh()->status);
    }

    public function test_manager_can_close(): void
    {
        $workOrder = $this->workOrder(['status' => 'resolu']);

        $this->actingAs(User::factory()->manager()->create())
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'ferme'])
            ->assertSessionHasNoErrors();
        $this->assertSame('ferme', $workOrder->fresh()->status);
    }

    // ===== KPI =====

    public function test_resolved_this_week_counts_on_completion_date(): void
    {
        $agent = User::factory()->housekeeping()->create();
        // Résolu il y a un mois, simplement commenté/modifié hier : ne doit pas compter.
        $old = $this->workOrder(['reported_by' => $agent->id, 'status' => 'resolu', 'completed_at' => now()->subMonth()]);
        $old->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
        $this->workOrder(['reported_by' => $agent->id, 'status' => 'resolu', 'completed_at' => now()->subDays(2)]);

        $pulse = $this->actingAs($agent)->get(route('housekeeping.dashboard'))->assertOk()->viewData('pulse');
        $resolved = collect($pulse)->firstWhere('label', 'Résolus cette semaine');

        $this->assertSame(1, $resolved['value']);
    }
}
