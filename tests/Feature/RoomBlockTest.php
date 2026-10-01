<?php

namespace Tests\Feature;

use App\Enums\RoomOccupancy;
use App\Models\Room;
use App\Models\RoomBlock;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\GuestRoomAtRiskNotification;
use App\Notifications\RoomBlockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoomBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private User $head;
    private User $reception;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->agent = User::factory()->housekeeping()->create();
        $this->head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->reception = User::factory()->reception()->create();
        $this->room = Room::create(['number' => '214', 'floor' => '2']);
    }

    private function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Climatisation · Chambre 214',
            'room_id' => $this->room->id,
            'reported_by' => $this->agent->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            'room_occupancy' => RoomOccupancy::Libre,
            ...$attributes,
        ]);
    }

    // ===== Occupation déclarée au signalement =====

    public function test_occupancy_is_required_for_a_room(): void
    {
        $this->actingAs($this->agent)
            ->postJson(route('quick-reports.store'), ['room_number' => '214', 'category' => 'clim'])
            ->assertJsonValidationErrors(['room_occupancy']);
    }

    public function test_guest_out_sets_a_deadline_before_their_return(): void
    {
        $this->travelTo(today()->setTime(10, 0));

        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'category' => 'clim', 'room_occupancy' => 'client_absent',
        ])->assertCreated();

        $workOrder = WorkOrder::firstOrFail();
        $this->assertSame(RoomOccupancy::ClientAbsent, $workOrder->room_occupancy);
        $this->assertSame(today()->setTime(17, 0)->toDateTimeString(), $workOrder->due_date->toDateTimeString());
    }

    public function test_late_report_still_leaves_two_hours_to_repair(): void
    {
        $this->travelTo(today()->setTime(16, 30));

        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'category' => 'clim', 'room_occupancy' => 'client_absent',
        ]);

        $this->assertSame(today()->setTime(18, 30)->toDateTimeString(), WorkOrder::firstOrFail()->due_date->toDateTimeString());
    }

    public function test_urgent_issue_with_guest_inside_alerts_reception_at_once(): void
    {
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'category' => 'electricite', 'room_occupancy' => 'client_present', 'urgent' => 1,
        ])->assertCreated();

        Notification::assertSentTo($this->reception, GuestRoomAtRiskNotification::class);
        $this->assertNotNull(WorkOrder::firstOrFail()->reception_alerted_at);
    }

    public function test_minor_issue_with_guest_inside_does_not_disturb_reception(): void
    {
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '214', 'category' => 'tv', 'room_occupancy' => 'client_present',
        ])->assertCreated();

        Notification::assertNotSentTo($this->reception, GuestRoomAtRiskNotification::class);
    }

    // ===== Retour du client : alerte planifiée =====

    public function test_reception_is_warned_once_before_guest_returns_to_a_broken_room(): void
    {
        $late = $this->workOrder(['room_occupancy' => RoomOccupancy::ClientAbsent, 'due_date' => now()->addMinutes(45)]);
        $this->workOrder(['room_occupancy' => RoomOccupancy::ClientAbsent, 'due_date' => now()->addHours(4)]);
        $this->workOrder(['room_occupancy' => RoomOccupancy::ClientAbsent, 'due_date' => now()->addMinutes(30), 'status' => 'resolu']);

        $this->artisan('rooms:watch-guest-returns')->assertSuccessful();
        $this->artisan('rooms:watch-guest-returns')->assertSuccessful();

        Notification::assertSentToTimes($this->reception, GuestRoomAtRiskNotification::class, 1);
        $this->assertNotNull($late->fresh()->reception_alerted_at);
        // Suivie dans l'encadré « Tâches automatiques » du tableau de bord admin.
        $this->assertNotNull(\App\Support\SchedulerHealth::lastRun('guest_returns'));
    }

    // ===== Cycle du blocage =====

    public function test_head_requests_reception_approves_head_releases(): void
    {
        $workOrder = $this->workOrder();

        // 1. La gouvernante demande.
        $this->actingAs($this->head)->post(route('room-blocks.store', $workOrder))->assertSessionHas('success');
        $block = RoomBlock::firstOrFail();
        $this->assertSame(RoomBlock::REQUESTED, $block->status);
        Notification::assertSentTo($this->reception, RoomBlockNotification::class);
        $this->assertSame('disponible', $this->room->fresh()->status);

        // 2. La réception accepte : la chambre sort de la vente.
        $this->actingAs($this->reception)->post(route('room-blocks.approve', $block))->assertSessionHas('success');
        $this->assertSame(RoomBlock::BLOCKED, $block->fresh()->status);
        $this->assertSame('maintenance', $this->room->fresh()->status);
        Notification::assertSentTo($this->head, RoomBlockNotification::class);

        // 3. Après vérification, la gouvernante remet en vente.
        $this->actingAs($this->head)->post(route('room-blocks.release', $block))->assertSessionHas('success');
        $this->assertSame(RoomBlock::RELEASED, $block->fresh()->status);
        $this->assertSame('disponible', $this->room->fresh()->status);
        Notification::assertSentToTimes($this->reception, RoomBlockNotification::class, 2);
    }

    public function test_reception_can_refuse_with_a_reason(): void
    {
        $block = RoomBlock::create(['room_id' => $this->room->id, 'status' => RoomBlock::REQUESTED, 'reason' => 'Clim', 'requested_by' => $this->head->id]);

        $this->actingAs($this->reception)->post(route('room-blocks.refuse', $block), ['decision_note' => 'Client VIP ce soir']);

        $this->assertSame(RoomBlock::REFUSED, $block->fresh()->status);
        $this->assertSame('disponible', $this->room->fresh()->status);
        Notification::assertSentTo($this->head, RoomBlockNotification::class,
            fn (RoomBlockNotification $n) => str_contains($n->toArray($this->head)['message'], 'Client VIP ce soir'));
    }

    public function test_each_step_is_reserved_to_the_right_people(): void
    {
        $workOrder = $this->workOrder();

        // Un agent ou la réception ne demandent pas de blocage.
        $this->actingAs($this->agent)->post(route('room-blocks.store', $workOrder))->assertForbidden();
        $this->actingAs($this->reception)->post(route('room-blocks.store', $workOrder))->assertForbidden();

        // La gouvernante ne valide pas sa propre demande.
        $block = RoomBlock::create(['room_id' => $this->room->id, 'status' => RoomBlock::REQUESTED, 'reason' => 'Clim', 'requested_by' => $this->head->id]);
        $this->actingAs($this->head)->post(route('room-blocks.approve', $block))->assertForbidden();

        // La réception ne remet pas en vente : c'est la gouvernante, après vérification.
        $block->update(['status' => RoomBlock::BLOCKED]);
        $this->actingAs($this->reception)->post(route('room-blocks.release', $block))->assertForbidden();
    }

    public function test_head_of_another_service_cannot_request_on_housekeeping_orders(): void
    {
        $receptionHead = User::factory()->reception()->create(['is_department_head' => true]);

        $this->actingAs($receptionHead)->post(route('room-blocks.store', $this->workOrder()))->assertForbidden();
    }

    public function test_a_room_cannot_have_two_active_blocks(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs($this->head)->post(route('room-blocks.store', $workOrder));
        $this->actingAs($this->head)->post(route('room-blocks.store', $workOrder))->assertSessionHas('warning');

        $this->assertSame(1, RoomBlock::count());
    }

    // ===== Écrans =====

    public function test_blocks_page_access_and_candidates(): void
    {
        $workOrder = $this->workOrder();

        $this->actingAs($this->head)->get(route('room-blocks.index'))
            ->assertOk()
            ->assertSee('Chambre 214')
            ->assertSee(route('room-blocks.store', $workOrder), false);
        $this->actingAs($this->reception)->get(route('room-blocks.index'))->assertOk();
        $this->actingAs(User::factory()->manager()->create())->get(route('room-blocks.index'))->assertOk();

        $this->actingAs($this->agent)->get(route('room-blocks.index'))->assertForbidden();
        $this->actingAs(User::factory()->technicien()->create())->get(route('room-blocks.index'))->assertForbidden();
    }

    public function test_blocks_page_flags_a_blocked_room_whose_order_was_cancelled(): void
    {
        $workOrder = $this->workOrder(['status' => 'annule']);
        RoomBlock::create(['room_id' => $this->room->id, 'work_order_id' => $workOrder->id, 'status' => RoomBlock::BLOCKED,
            'reason' => 'Clim', 'requested_by' => $this->head->id, 'decided_by' => $this->reception->id, 'decided_at' => now()]);

        $this->actingAs($this->head)->get(route('room-blocks.index'))->assertOk()->assertSee('OT annulé');
    }

    public function test_work_order_page_shows_the_room_card_with_request_button_for_the_head(): void
    {
        $workOrder = $this->workOrder(['room_occupancy' => RoomOccupancy::ClientAbsent, 'due_date' => now()->addHours(3)]);

        $this->actingAs($this->head)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Chambre et client')
            ->assertSee('Client sorti')
            ->assertSee('Demander le blocage');

        $this->actingAs($this->agent)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Chambre et client')
            ->assertDontSee('Demander le blocage');
    }

    public function test_block_notification_leads_reception_to_the_blocks_page(): void
    {
        Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));
        $block = RoomBlock::create(['room_id' => $this->room->id, 'status' => RoomBlock::REQUESTED, 'reason' => 'Clim', 'requested_by' => $this->head->id]);
        $this->reception->notify(new RoomBlockNotification($block, RoomBlockNotification::REQUESTED));
        $id = $this->reception->notifications()->firstOrFail()->id;

        $this->actingAs($this->reception)->post(route('notifications.read', $id))
            ->assertRedirect(route('room-blocks.index'));
    }
}
