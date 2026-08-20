<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartReservationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkOrder(?User $assignee = null): WorkOrder
    {
        $reporter = User::factory()->reception()->create();

        return WorkOrder::create([
            'title' => 'Fuite robinet',
            'reported_by' => $reporter->id,
            'assigned_to' => $assignee?->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
    }

    private function makePart(): Part
    {
        return Part::create([
            'sku' => 'PART-001',
            'name' => 'Joint robinet',
            'unit' => 'unité',
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'reorder_threshold' => 5,
            'unit_cost' => 2.5,
            'is_active' => true,
        ]);
    }

    public function test_uninvolved_user_cannot_reserve_parts_on_a_work_order(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $part = $this->makePart();

        // Un utilisateur housekeeping quelconque, sans lien avec cet OT, ne doit pas
        // pouvoir réserver du stock dessus (l'ancien code ne vérifiait aucune autorisation).
        $bystander = User::factory()->housekeeping()->create();

        $this->actingAs($bystander)
            ->post(route('work-orders.reservations.store', $workOrder), [
                'part_id' => $part->id,
                'quantity' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('part_reservations', 0);
    }

    public function test_assigned_technician_can_reserve_parts_on_their_work_order(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $part = $this->makePart();

        $this->actingAs($technician)
            ->post(route('work-orders.reservations.store', $workOrder), [
                'part_id' => $part->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('part_reservations', [
            'work_order_id' => $workOrder->id,
            'part_id' => $part->id,
            'quantity' => 2,
        ]);
    }

    public function test_uninvolved_user_cannot_cancel_a_reservation(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $part = $this->makePart();

        $reservation = $workOrder->partReservations()->create([
            'part_id' => $part->id,
            'quantity' => 1,
            'status' => 'reservee',
            'reserved_by' => $technician->id,
        ]);

        $bystander = User::factory()->reception()->create();

        $this->actingAs($bystander)
            ->delete(route('work-orders.reservations.cancel', [$workOrder, $reservation]))
            ->assertForbidden();

        $this->assertDatabaseHas('part_reservations', ['id' => $reservation->id, 'status' => 'reservee']);
    }

    public function test_manager_can_manage_reservations_on_any_work_order(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $part = $this->makePart();
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('work-orders.reservations.store', $workOrder), [
                'part_id' => $part->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('part_reservations', 1);
    }
}
