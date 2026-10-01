<?php

namespace Tests\Feature;

use App\Models\InterventionReport;
use App\Models\Part;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Celui qui fait ne contrôle pas » : l'admin et le manager supervisent ; seule la
 * personne assignée exécute (chrono, rapport, signature, sortie de stock).
 */
class SeparationOfDutiesTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;
    private User $chef;
    private WorkOrder $workOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technician = User::factory()->technicien()->create(['name' => 'Paul']);
        $this->chef = User::factory()->admin()->create(['name' => 'Chef Maintenance']);
        $this->workOrder = WorkOrder::create([
            'title' => 'Fuite chambre 312',
            'assigned_to' => $this->technician->id,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
    }

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    // ===== Le superviseur ne fait pas le travail du technicien =====

    public function test_admin_and_manager_cannot_start_the_timer_of_someone_elses_work_order(): void
    {
        foreach ([$this->chef, User::factory()->manager()->create()] as $supervisor) {
            $this->actingAs($supervisor)->post(route('work-orders.sessions.start', $this->workOrder))->assertForbidden();
        }

        $this->assertSame(0, $this->workOrder->interventionSessions()->count());
    }

    public function test_admin_cannot_write_or_sign_the_report_in_the_technicians_place(): void
    {
        $this->actingAs($this->chef)->post(route('work-orders.report.store', $this->workOrder), [
            'work_performed' => 'Joint changé',
            'signed_by_name' => 'Client',
            'signature' => self::SIGNATURE,
        ])->assertForbidden();

        $this->assertNull($this->workOrder->fresh()->interventionReport);
        $this->assertSame('ouvert', $this->workOrder->fresh()->status);
    }

    public function test_admin_cannot_withdraw_parts_but_can_still_reserve_them(): void
    {
        $part = Part::create(['sku' => 'J-01', 'name' => 'Joint', 'unit' => 'unité', 'quantity_on_hand' => 10, 'quantity_reserved' => 0, 'reorder_threshold' => 2, 'unit_cost' => 1, 'is_active' => true]);

        $this->actingAs($this->chef)->post(route('work-orders.reservations.store', $this->workOrder), ['part_id' => $part->id, 'quantity' => 1])->assertRedirect();
        $reservation = $this->workOrder->partReservations()->firstOrFail();

        $this->actingAs($this->chef)->post(route('work-orders.reservations.withdraw', [$this->workOrder, $reservation]))->assertForbidden();
        $this->actingAs($this->technician)->post(route('work-orders.reservations.withdraw', [$this->workOrder, $reservation]))->assertRedirect();
    }

    public function test_supervisor_still_pilots_the_work_order(): void
    {
        $this->actingAs($this->chef)
            ->patch(route('work-orders.status.update', $this->workOrder), ['status' => 'en_attente'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->chef)
            ->post(route('work-orders.comments.store', $this->workOrder), ['content' => 'Paul, vérifie aussi la douche.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('en_attente', $this->workOrder->fresh()->status);
    }

    public function test_show_page_hides_execution_buttons_from_supervisor(): void
    {
        $this->actingAs($this->chef)->get(route('work-orders.show', $this->workOrder))
            ->assertOk()
            ->assertDontSee('▶ Démarrer')
            ->assertDontSee('Enregistrer le rapport')
            ->assertSee('Chrono géré par Paul')
            ->assertSee("Je m'en charge", false);

        $this->actingAs($this->technician)->get(route('work-orders.show', $this->workOrder))
            ->assertSee('▶ Démarrer')
            ->assertSee('Enregistrer le rapport');
    }

    // ===== « Je m'en charge » =====

    public function test_chef_can_take_over_and_then_works_under_his_own_name(): void
    {
        $this->actingAs($this->chef)->post(route('work-orders.take-over', $this->workOrder))->assertRedirect();

        $this->assertSame($this->chef->id, $this->workOrder->fresh()->assigned_to);
        $this->assertStringContainsString('à la place de Paul', $this->workOrder->statusHistories()->latest('id')->value('note'));

        $this->actingAs($this->chef)->post(route('work-orders.sessions.start', $this->workOrder))->assertRedirect();
        $this->assertSame($this->chef->id, $this->workOrder->interventionSessions()->value('technician_id'));

        // Paul n'est plus l'intervenant : il ne peut plus chronométrer.
        $this->actingAs($this->technician)->post(route('work-orders.sessions.stop', $this->workOrder))->assertForbidden();
    }

    public function test_technician_and_requester_cannot_take_over(): void
    {
        $this->actingAs(User::factory()->technicien()->create())->post(route('work-orders.take-over', $this->workOrder))->assertForbidden();
        $this->actingAs($this->workOrder->reporter)->post(route('work-orders.take-over', $this->workOrder))->assertForbidden();
    }

    // ===== Celui qui a réparé ne fait pas le contrôle qualité =====

    public function test_executor_cannot_review_quality_but_another_supervisor_can(): void
    {
        $this->workOrder->update(['assigned_to' => $this->chef->id, 'status' => 'resolu']);
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->chef)->get(route('quality-controls.create', $this->workOrder))->assertForbidden();
        $this->actingAs($this->chef)->post(route('quality-controls.store', $this->workOrder), [])->assertForbidden();
        $this->actingAs($this->chef)->get(route('work-orders.show', $this->workOrder))
            ->assertSee('le contrôle qualité doit être fait par un autre');

        $this->actingAs($otherAdmin)->get(route('quality-controls.create', $this->workOrder))->assertOk();
    }

    public function test_reviewer_check_also_covers_past_intervention_sessions(): void
    {
        // Le chef a chronométré puis réaffecté l'OT : il a quand même travaillé dessus.
        $this->workOrder->update(['status' => 'resolu']);
        $this->workOrder->interventionSessions()->create(['technician_id' => $this->chef->id, 'started_at' => now()->subHour(), 'ended_at' => now(), 'duration_minutes' => 60]);

        $this->assertTrue($this->workOrder->wasExecutedBy($this->chef));
        $this->actingAs($this->chef)->get(route('quality-controls.create', $this->workOrder))->assertForbidden();
    }

    // ===== Rapport signé verrouillé, brouillon non signé =====

    public function test_signed_report_cannot_be_overwritten(): void
    {
        $this->actingAs($this->technician)->post(route('work-orders.report.store', $this->workOrder), [
            'work_performed' => 'Joint changé',
            'signed_by_name' => 'M. Client',
            'signature' => self::SIGNATURE,
        ]);
        $this->assertTrue($this->workOrder->fresh()->interventionReport->is_signed);

        $this->actingAs($this->technician)->post(route('work-orders.report.store', $this->workOrder), [
            'work_performed' => 'Texte modifié après signature',
        ])->assertSessionHas('warning');

        $this->assertSame('Joint changé', InterventionReport::where('work_order_id', $this->workOrder->id)->value('work_performed'));
    }

    public function test_draft_without_signature_does_not_resolve_the_work_order(): void
    {
        $this->actingAs($this->technician)->post(route('work-orders.report.store', $this->workOrder), [
            'work_performed' => 'Diagnostic : joint usé, pièce commandée',
            'signature' => '',
        ]);

        $this->assertFalse($this->workOrder->fresh()->interventionReport->is_signed);
        $this->assertSame('ouvert', $this->workOrder->fresh()->status);
    }
}
