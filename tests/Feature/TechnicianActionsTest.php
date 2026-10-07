<?php

namespace Tests\Feature;

use App\Models\PartRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\PartRequestedNotification;
use App\Notifications\PartRequestHandledNotification;
use App\Notifications\WorkOrderAcknowledgedNotification;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Ce que le technicien peut maintenant faire lui-même : dire « J'ai vu, je m'en
 * occupe », signaler une autre panne trouvée sur place, demander une pièce absente.
 */
class TechnicianActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;

    private User $manager;

    /** Numéros de chambre distincts d'un OT à l'autre. */
    private int $rooms = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->technician = User::factory()->technicien()->create(['name' => 'Kouassi Tech']);
        $this->manager = User::factory()->manager()->create(['receives_maintenance_alerts' => true]);
    }

    private function workOrder(string $priority = 'moyenne', array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite lavabo',
            'room_id' => Room::create(['number' => (string) (100 + ++$this->rooms), 'floor' => 'Étage 1'])->id,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'assigned_to' => $this->technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', $priority)->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    public function test_technician_acknowledges_a_new_work_order_and_the_manager_sees_it(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Nouvel ordre pour vous')->assertSee("J'ai vu, je m'en occupe", false);
        $this->actingAs($this->manager)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Pas encore vu')->assertDontSee('Nouvel ordre pour vous');

        $this->actingAs($this->technician)->post(route('work-orders.acknowledge', $workOrder))->assertRedirect();

        $this->assertNotNull($workOrder->fresh()->acknowledged_at);
        $this->assertDatabaseHas('work_order_status_histories', ['work_order_id' => $workOrder->id, 'note' => 'Vu par Kouassi Tech : je m\'en occupe.']);
        $this->actingAs($this->manager)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertDontSee('Pas encore vu')->assertSee('Vu le');
        // Une seule fois par affectation.
        $this->actingAs($this->technician)->post(route('work-orders.acknowledge', $workOrder))->assertForbidden();
    }

    public function test_only_the_assigned_technician_can_acknowledge(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs(User::factory()->technicien()->create())->post(route('work-orders.acknowledge', $workOrder))->assertForbidden();
        $this->actingAs($this->manager)->post(route('work-orders.acknowledge', $workOrder))->assertForbidden();
    }

    public function test_urgent_acknowledgement_tells_the_on_call_team(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(10, 0));

        $this->actingAs($this->technician)->post(route('work-orders.acknowledge', $this->workOrder('urgente')));
        Notification::assertSentTo($this->manager, WorkOrderAcknowledgedNotification::class);

        $this->actingAs($this->technician)->post(route('work-orders.acknowledge', $this->workOrder()));
        Notification::assertSentToTimes($this->manager, WorkOrderAcknowledgedNotification::class, 1);
    }

    public function test_starting_the_chrono_counts_as_seen_and_reassignment_resets_it(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs($this->technician)->post(route('work-orders.sessions.start', $workOrder));
        $this->assertNotNull($workOrder->fresh()->acknowledged_at);

        $workOrder->fresh()->update(['assigned_to' => User::factory()->technicien()->create()->id]);
        $this->assertNull($workOrder->fresh()->acknowledged_at);
    }

    public function test_technician_can_report_another_fault_from_the_menu_and_follow_it(): void
    {
        $this->assertContains('quick-reports.create', array_column(Navigation::forBottomNav($this->technician), 'route'));
        $room = Room::create(['number' => '214', 'floor' => 'Étage 2']);

        $this->actingAs($this->technician)->get(route('quick-reports.create'))
            ->assertOk()->assertSee('Signaler une panne')->assertDontSee("Réclamation d'un client", false);

        $this->actingAs($this->technician)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'room_occupancy' => 'libre', 'category' => 'clim',
        ])->assertCreated();

        $reported = WorkOrder::where('room_id', $room->id)->firstOrFail();
        $this->assertSame($this->technician->id, $reported->reported_by);
        $this->actingAs($this->technician)->get(route('quick-reports.sent', $reported))->assertOk();
        $this->actingAs($this->technician)->get(route('work-orders.show', $reported))->assertOk();
    }

    public function test_technician_requests_a_missing_part_and_the_manager_handles_it(): void
    {
        Notification::fake();
        $workOrder = $this->workOrder(attributes: ['status' => 'en_cours']);

        $this->actingAs($this->technician)->post(route('work-orders.part-requests.store', $workOrder), [
            'description' => 'Mitigeur Grohe 1/2', 'quantity' => 1, 'suspend' => '1',
        ])->assertRedirect();

        $request = PartRequest::firstOrFail();
        $this->assertSame('demandee', $request->status);
        $this->assertSame('en_attente', $workOrder->fresh()->status);
        $this->assertDatabaseHas('work_order_status_histories', ['work_order_id' => $workOrder->id, 'new_status' => 'en_attente', 'note' => 'Pièce manquante : 1 × Mitigeur Grohe 1/2.']);
        Notification::assertSentTo($this->manager, PartRequestedNotification::class);

        $this->actingAs($this->manager)->get(route('manager.dashboard'))->assertOk()->assertSee('Pièce demandée : 1 × Mitigeur Grohe 1/2');
        $this->actingAs($this->manager)->get(route('work-orders.show', $workOrder))->assertOk()->assertSee('Marquer traitée');

        // Le technicien ne traite pas lui-même sa demande.
        $this->actingAs($this->technician)->post(route('part-requests.handle', $request))->assertForbidden();

        $this->actingAs($this->manager)->post(route('part-requests.handle', $request), ['handling_note' => 'Commandée, livraison jeudi'])->assertRedirect();
        $this->assertSame('traitee', $request->fresh()->status);
        Notification::assertSentTo($this->technician, PartRequestHandledNotification::class);

        $this->actingAs($this->technician)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Traitée')->assertSee('Commandée, livraison jeudi');
    }

    public function test_part_request_keeps_the_status_when_not_asked_to_suspend(): void
    {
        $workOrder = $this->workOrder(attributes: ['status' => 'en_cours']);

        $this->actingAs($this->technician)->post(route('work-orders.part-requests.store', $workOrder), [
            'description' => 'Joint 20 mm', 'quantity' => 2, 'suspend' => '0',
        ])->assertRedirect();

        $this->assertSame('en_cours', $workOrder->fresh()->status);
        $this->actingAs(User::factory()->technicien()->create())
            ->post(route('work-orders.part-requests.store', $workOrder), ['description' => 'X', 'quantity' => 1])
            ->assertForbidden();
    }
}
