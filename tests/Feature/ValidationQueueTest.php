<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File « Validation » : réparations déclarées terminées à contrôler, sans que
 * l'intervenant puisse contrôler son propre travail.
 */
class ValidationQueueTest extends TestCase
{
    use RefreshDatabase;

    private function workOrder(array $attributes): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Clim chambre 105',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            ...$attributes,
        ]);
    }

    public function test_manager_sees_repairs_to_review_with_a_review_button(): void
    {
        $repaired = $this->workOrder(['title' => 'Robinet réparé', 'status' => 'resolu', 'completed_at' => now()->subHour(),
            'assigned_to' => User::factory()->technicien()->create()->id]);
        $this->workOrder(['title' => 'Clim encore en panne', 'status' => 'en_cours']);

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('quality-controls.index'))
            ->assertOk()
            ->assertSee('Robinet réparé')
            ->assertDontSee('Clim encore en panne')
            ->assertSee('href="'.route('quality-controls.create', $repaired).'"', false);
    }

    public function test_the_one_who_repaired_cannot_review_it(): void
    {
        $manager = User::factory()->manager()->create();
        $this->workOrder(['status' => 'resolu', 'assigned_to' => $manager->id]);

        $this->actingAs($manager)->get(route('quality-controls.index'))
            ->assertSee('contrôle par un autre responsable');
    }

    public function test_field_roles_have_no_access(): void
    {
        $this->actingAs(User::factory()->technicien()->create())
            ->get(route('quality-controls.index'))
            ->assertForbidden();
    }
}
