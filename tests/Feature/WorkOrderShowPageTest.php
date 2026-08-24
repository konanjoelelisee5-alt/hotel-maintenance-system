<?php

namespace Tests\Feature;

use App\Models\CorrectionRequest;
use App\Models\Part;
use App\Models\PartReservation;
use App\Models\SlaPolicy;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderQualityControl;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderShowPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeFullyLoadedWorkOrder(): WorkOrder
    {
        $technician = User::factory()->technicien()->create();
        $reporter = User::factory()->housekeeping()->create();
        $manager = User::factory()->manager()->create();

        $slaPolicy = SlaPolicy::create([
            'name' => 'Standard',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 480,
        ]);

        $workOrder = WorkOrder::create([
            'title' => 'Climatiseur en panne',
            'description' => 'Ne refroidit plus depuis ce matin.',
            'reported_by' => $reporter->id,
            'assigned_to' => $technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'haute')->firstOrFail()->id,
            'status' => 'resolu',
            'sla_policy_id' => $slaPolicy->id,
            'sla_response_due_at' => now()->addHour(),
            'sla_resolution_due_at' => now()->addHours(8),
        ]);

        $workOrder->comments()->create(['user_id' => $reporter->id, 'content' => 'Merci de vérifier rapidement.']);
        $workOrder->comments()->create(['user_id' => $technician->id, 'content' => 'Pris en charge, sur place demain.']);

        $workOrder->interventionSessions()->create([
            'technician_id' => $technician->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'duration_minutes' => 60,
        ]);

        $part = Part::create([
            'sku' => 'FILT-01', 'name' => 'Filtre climatiseur', 'unit' => 'unité',
            'quantity_on_hand' => 10, 'quantity_reserved' => 0,
            'reorder_threshold' => 2, 'unit_cost' => 15, 'is_active' => true,
        ]);
        PartReservation::create([
            'part_id' => $part->id, 'work_order_id' => $workOrder->id,
            'quantity' => 1, 'status' => 'reservee', 'reserved_by' => $technician->id,
        ]);

        $qc = WorkOrderQualityControl::create([
            'work_order_id' => $workOrder->id,
            'reviewed_by' => $manager->id,
            'status' => 'rejete',
        ]);

        CorrectionRequest::create([
            'work_order_id' => $workOrder->id,
            'quality_control_id' => $qc->id,
            'requested_by' => $manager->id,
            'description' => 'Refaire l\'étanchéité du raccord.',
            'status' => 'ouverte',
        ]);

        return $workOrder;
    }

    public function test_admin_can_view_the_fully_loaded_show_page(): void
    {
        $workOrder = $this->makeFullyLoadedWorkOrder();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('work-orders.show', $workOrder));

        $response->assertOk();
        $response->assertSee('Climatiseur en panne');
        $response->assertSee('Contrôle qualité');
        $response->assertSee('Demandes de correction');
        $response->assertSee('Filtre climatiseur');
        $response->assertSee('Merci de vérifier rapidement.');
    }

    public function test_assigned_technician_sees_action_forms(): void
    {
        $workOrder = $this->makeFullyLoadedWorkOrder();

        $response = $this->actingAs($workOrder->assignee)->get(route('work-orders.show', $workOrder));

        $response->assertOk();
        $response->assertSee('Changer le statut');
        $response->assertSee("Rapport d'intervention", false);
        // Le contrôle qualité est réservé admin/manager : un technicien ne doit pas voir ce bloc.
        $response->assertDontSee('Démarrer un contrôle qualité');
    }

    public function test_reporter_can_view_but_not_see_intervention_actions(): void
    {
        $workOrder = $this->makeFullyLoadedWorkOrder();

        $response = $this->actingAs($workOrder->reporter)->get(route('work-orders.show', $workOrder));

        $response->assertOk();
        // Le rapporteur (housekeeping) voit l'OT mais ne peut pas intervenir dessus.
        $response->assertDontSee('Changer le statut');
        $response->assertDontSee('Démarrer');
    }

    public function test_uninvolved_technician_cannot_view_the_work_order(): void
    {
        $workOrder = $this->makeFullyLoadedWorkOrder();
        $otherTechnician = User::factory()->technicien()->create();

        $this->actingAs($otherTechnician)
            ->get(route('work-orders.show', $workOrder))
            ->assertForbidden();
    }
}
