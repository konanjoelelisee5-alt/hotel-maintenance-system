<?php

namespace Tests\Feature;

use App\Enums\RoomOccupancy;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\GuestRoomAtRiskNotification;
use App\Notifications\GuestSituationNotification;
use App\Notifications\WorkOrderProgressNotification;
use App\Support\ReceptionDesk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La réception : retrouver une chambre et répondre au client, déclarer la situation
 * du client, transmettre une réclamation, savoir qui appeler.
 */
class ReceptionDeskTest extends TestCase
{
    use RefreshDatabase;

    private User $reception;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Room::create(['number' => '305', 'floor' => 'Étage 3']);
        $this->reception = User::factory()->reception()->create();
        $this->agent = User::factory()->housekeeping()->create();
    }

    /** Une panne signalée par le Housekeeping (pas par la réception). */
    private function hkReport(string $occupancy = 'client_present'): WorkOrder
    {
        $this->actingAs($this->agent)->postJson(route('quick-reports.store'), [
            'room_number' => '305', 'room_occupancy' => $occupancy, 'category' => 'clim',
        ])->assertCreated();

        return WorkOrder::latest('id')->firstOrFail();
    }

    public function test_reception_finds_a_room_and_what_to_tell_the_guest_whoever_reported(): void
    {
        // Midi : « dans 2 heures » reste aujourd'hui, quelle que soit l'heure du test.
        $this->travelTo(now()->setTime(12, 0));
        $workOrder = $this->hkReport();
        $technician = User::factory()->technicien()->create(['name' => 'Kouassi Tech']);
        $workOrder->update(['assigned_to' => $technician->id, 'scheduled_at' => now()->addHours(2)]);

        $this->actingAs($this->reception)->get(route('reception.dashboard', ['chambre' => '305']))
            ->assertOk()
            ->assertSee('À dire au client')
            ->assertSee('Climatisation')
            ->assertSee('Un technicien passera aujourd&#039;hui vers', false)
            ->assertSee('Kouassi Tech')
            ->assertSee($workOrder->code());

        $this->actingAs($this->reception)->get(route('reception.dashboard', ['chambre' => '999']))
            ->assertOk()->assertSee('Aucune chambre « 999 »');
    }

    public function test_home_shows_guests_concerned_on_call_and_blocks_to_decide(): void
    {
        $this->hkReport('client_present');
        $manager = User::factory()->manager()->create(['name' => 'Awa Manager', 'phone' => '+2250700000001', 'receives_maintenance_alerts' => true]);
        $this->travelTo(now()->setTime(10, 0));

        $this->assertContains($manager->id, ReceptionDesk::onCall()['people']->pluck('id')->all());
        $this->actingAs($this->reception)->get(route('reception.dashboard'))
            ->assertOk()
            ->assertSee('Clients concernés maintenant')->assertSee('Client dans la chambre')
            ->assertSee('Astreinte de jour')->assertSee('Awa Manager')->assertSee('tel:+2250700000001', false)
            ->assertSee('Blocages à décider');
    }

    public function test_reception_tells_maintenance_the_guest_was_moved(): void
    {
        $workOrder = $this->hkReport('client_present');
        $technician = User::factory()->technicien()->create();
        $workOrder->update(['assigned_to' => $technician->id]);
        Notification::fake();

        $this->actingAs($this->reception)->post(route('reception.situation', $workOrder->room), [
            'situation' => 'reloge', 'other_room' => '312',
        ])->assertRedirect(route('reception.dashboard', ['chambre' => '305']));

        $workOrder->refresh();
        $this->assertSame(RoomOccupancy::Libre, $workOrder->room_occupancy);
        $this->assertNull($workOrder->due_date);
        $this->assertStringContainsString('client relogé en 312', $workOrder->comments()->value('content'));
        Notification::assertSentTo($technician, GuestSituationNotification::class);
    }

    public function test_guest_out_sets_a_deadline_and_needs_a_time(): void
    {
        $workOrder = $this->hkReport('libre');
        $this->travelTo(now()->setTime(9, 0));

        $this->actingAs($this->reception)->post(route('reception.situation', $workOrder->room), ['situation' => 'sorti'])
            ->assertSessionHasErrors('time');

        $this->actingAs($this->reception)->post(route('reception.situation', $workOrder->room), ['situation' => 'sorti', 'time' => '16:30']);
        $workOrder->refresh();
        $this->assertSame(RoomOccupancy::ClientAbsent, $workOrder->room_occupancy);
        $this->assertSame('16:30', $workOrder->due_date->format('H:i'));
        $this->assertNull($workOrder->reception_alerted_at);
    }

    public function test_only_reception_declares_the_guest_situation(): void
    {
        $workOrder = $this->hkReport();

        $this->actingAs($this->agent)->post(route('reception.situation', $workOrder->room), ['situation' => 'libre'])->assertForbidden();
        $this->actingAs(User::factory()->technicien()->create())->post(route('reception.situation', $workOrder->room), ['situation' => 'libre'])->assertForbidden();
    }

    public function test_guest_complaint_is_a_client_request_and_repair_reminds_to_call_the_guest(): void
    {
        $this->actingAs($this->reception)->get(route('quick-reports.create', ['chambre' => '305']))
            ->assertOk()->assertSee("Réclamation d'un client", false)->assertSee('305');

        $this->actingAs($this->reception)->postJson(route('quick-reports.store'), [
            'room_number' => '305', 'room_occupancy' => 'client_present', 'category' => 'tv', 'guest_complaint' => 1,
            'note' => 'Le client dit que la télé ne s\'allume plus.',
        ])->assertCreated();
        $workOrder = WorkOrder::where('reported_by', $this->reception->id)->firstOrFail();
        $this->assertSame('demande_client', $workOrder->type->code);

        $this->actingAs($this->reception)->get(route('quick-reports.sent', $workOrder))->assertOk()->assertSee('Signalement envoyé');

        Notification::fake();
        $technician = User::factory()->technicien()->create();
        $this->actingAs($technician);
        $workOrder->update(['assigned_to' => $technician->id, 'status' => 'resolu', 'completed_at' => now()]);
        Notification::assertSentTo($this->reception, WorkOrderProgressNotification::class,
            fn ($n) => str_contains($n->toArray($this->reception)['message'], 'Prévenez le client'));
    }

    public function test_reception_withdraws_or_completes_its_own_request(): void
    {
        $this->actingAs($this->reception)->postJson(route('quick-reports.store'), [
            'room_number' => '305', 'room_occupancy' => 'client_present', 'category' => 'eau', 'guest_complaint' => 1,
        ])->assertCreated();
        $workOrder = WorkOrder::where('reported_by', $this->reception->id)->firstOrFail();

        $this->actingAs($this->reception)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Ajouter une précision')->assertSee('Retirer ce signalement ?');

        $this->actingAs($this->reception)->postJson(route('quick-reports.complement', $workOrder), ['note' => 'Le client rappelle : ça déborde.'])
            ->assertCreated();
        $this->assertStringContainsString('ça déborde', $workOrder->comments()->value('content'));

        $this->actingAs($this->reception)->post(route('quick-reports.withdraw', $workOrder), ['reason' => 'doublon'])
            ->assertRedirect(route('work-orders.show', $workOrder));
        $this->assertSame('annule', $workOrder->fresh()->status);

        // Les rôles qui pilotent ou réparent n'ont pas ces gestes de demandeur.
        $this->actingAs(User::factory()->manager()->create())->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertDontSee('Ajouter une précision');
    }

    public function test_counter_screen_knows_when_something_changed(): void
    {
        $before = $this->actingAs($this->reception)->getJson(route('reception.state'))->assertOk()->json();

        $this->hkReport('client_present');

        $after = $this->actingAs($this->reception)->getJson(route('reception.state'))->json();
        $this->assertNotSame($before['signature'], $after['signature']);
    }

    public function test_shared_counter_computer_shows_who_is_connected_and_logs_out_when_idle(): void
    {
        $this->actingAs($this->reception)->get(route('reception.dashboard'))
            ->assertSee('Changer de réceptionniste')->assertSee('id="idle-logout"', false)->assertSee($this->reception->name);

        $this->actingAs(User::factory()->manager()->create())->get(route('manager.dashboard'))
            ->assertDontSee('Changer de réceptionniste')->assertDontSee('id="idle-logout"', false);
    }

    public function test_reception_head_has_a_monthly_report_of_guest_complaints(): void
    {
        $head = User::factory()->reception()->create(['is_department_head' => true]);
        $this->actingAs($this->reception)->postJson(route('quick-reports.store'), [
            'room_number' => '305', 'room_occupancy' => 'client_present', 'category' => 'tv', 'guest_complaint' => 1,
        ])->assertCreated();

        $response = $this->actingAs($head)->get(route('reception.monthly-report'))->assertOk()
            ->assertSee('1 réclamation(s) client')->assertDontSee('Voir les inspections');
        $this->assertSame(1, $response->viewData('stats')['complaints']);

        $this->actingAs($this->reception)->get(route('reception.monthly-report'))->assertForbidden();
    }

    public function test_guest_at_risk_alert_opens_the_room(): void
    {
        $workOrder = $this->hkReport();
        $this->reception->notify(new GuestRoomAtRiskNotification($workOrder->load('room'), GuestRoomAtRiskNotification::GUEST_INSIDE));
        $notification = $this->reception->notifications()->firstOrFail();

        $this->actingAs($this->reception)->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('reception.dashboard', ['chambre' => '305']));
    }
}
