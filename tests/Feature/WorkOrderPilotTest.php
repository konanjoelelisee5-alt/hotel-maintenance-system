<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\EscalationRule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche OT côté superviseur : panneau « Pilotage » (prochaine étape + actions
 * d'arbitre), annulation au lieu de suppression, fermeture par le contrôle qualité.
 */
class WorkOrderPilotTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create();
        $this->technician = User::factory()->technicien()->create(['name' => 'Paul']);
    }

    private function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Clim chambre 105',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    // ===== Prochaine étape selon l'état =====

    public function test_unassigned_work_order_suggests_assigning_a_technician(): void
    {
        $this->actingAs($this->manager)->get(route('work-orders.show', $this->workOrder()))
            ->assertSee("Pilotage de l'OT", false)
            ->assertSee('Affecter et planifier')
            ->assertDontSee('Avancement de mon intervention');
    }

    public function test_resolved_work_order_suggests_quality_control(): void
    {
        $this->actingAs($this->manager)
            ->get(route('work-orders.show', $this->workOrder(['assigned_to' => $this->technician->id, 'status' => 'resolu'])))
            ->assertSee('Faire le contrôle qualité');
    }

    public function test_closed_work_order_offers_no_action(): void
    {
        $this->actingAs($this->manager)
            ->get(route('work-orders.show', $this->workOrder(['assigned_to' => $this->technician->id, 'status' => 'ferme'])))
            ->assertSee('Rien à faire')
            ->assertDontSee('Annuler l&#039;OT', false)
            ->assertDontSee('Mettre en attente');
    }

    // ===== Suspendre / relancer =====

    public function test_suspend_requires_a_reason_and_resume_restores_previous_step(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => $this->technician->id, 'status' => 'en_cours', 'started_at' => now()->subHour()]);

        $this->actingAs($this->manager)->post(route('work-orders.suspend', $workOrder), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->manager)->post(route('work-orders.suspend', $workOrder), ['reason' => 'Compresseur commandé']);

        $this->assertSame('en_attente', $workOrder->fresh()->status);
        $this->actingAs($this->manager)->get(route('work-orders.show', $workOrder))
            ->assertSee('Compresseur commandé')
            ->assertSee('Relancer');

        $this->actingAs($this->manager)->post(route('work-orders.resume', $workOrder));
        $this->assertSame('en_cours', $workOrder->fresh()->status);
    }

    public function test_technician_must_give_a_reason_to_put_on_hold(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => $this->technician->id, 'status' => 'en_cours']);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'en_attente'])
            ->assertSessionHasErrors('note');
    }

    // ===== Annuler au lieu de supprimer =====

    public function test_cancel_keeps_the_work_order_with_its_reason(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => $this->technician->id, 'status' => 'en_cours']);
        $session = $workOrder->interventionSessions()->create(['technician_id' => $this->technician->id, 'started_at' => now()->subMinutes(10)]);

        $this->actingAs($this->manager)->post(route('work-orders.cancel', $workOrder), ['reason' => 'Doublon de OT-00012']);

        $this->assertSame('annule', $workOrder->fresh()->status);
        $this->assertSame('Annulé : Doublon de OT-00012', $workOrder->statusHistories()->latest('id')->value('note'));
        $this->assertTrue(ActivityLog::where('action', 'work_order.cancelled')->exists());
        $this->assertNotNull($session->fresh()->ended_at);
    }

    public function test_work_orders_can_no_longer_be_deleted(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs(User::factory()->admin()->create())->delete('/work-orders/'.$workOrder->id)->assertStatus(405);
        $this->assertNotNull($workOrder->fresh());
    }

    public function test_technician_cannot_pilot(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => $this->technician->id]);

        $this->actingAs($this->technician)->post(route('work-orders.cancel', $workOrder), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->technician)->post(route('work-orders.suspend', $workOrder), ['reason' => 'x'])->assertForbidden();
    }

    public function test_cancelled_work_order_is_excluded_from_sla_escalations(): void
    {
        $workOrder = $this->workOrder(['status' => 'annule']);
        $workOrder->update(['sla_response_due_at' => now()->subHour(), 'sla_resolution_due_at' => now()->subMinutes(5)]);
        EscalationRule::create(['name' => 'Retard', 'trigger_type' => 'resolution_depassee', 'offset_minutes' => 0, 'notify_target' => 'manager', 'is_active' => true]);

        $this->artisan('work-orders:check-sla');

        $this->assertFalse($workOrder->fresh()->sla_breached);
    }
}
