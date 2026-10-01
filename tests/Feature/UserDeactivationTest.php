<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\InterventionSession;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\WorkOrderReassignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserDeactivationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leaving;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->leaving = User::factory()->technicien()->create(['name' => 'Jean']);
    }

    private function workOrderFor(User $technician, string $title, string $status = 'ouvert'): WorkOrder
    {
        return WorkOrder::create([
            'title' => $title,
            'assigned_to' => $technician->id,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => $status,
        ]);
    }

    // ===== Méthode C : remplaçant par défaut + choix OT par OT =====

    public function test_default_replacement_with_per_work_order_override(): void
    {
        Notification::fake();
        $paul = User::factory()->technicien()->create(['name' => 'Paul']);
        $marc = User::factory()->technicien()->create(['name' => 'Marc']);
        $leak = $this->workOrderFor($this->leaving, 'Fuite chambre 312');
        $aircon = $this->workOrderFor($this->leaving, 'Clim chambre 105');
        $socket = $this->workOrderFor($this->leaving, 'Prise piscine');

        $this->actingAs($this->admin)->post(route('users.deactivate.store', $this->leaving), [
            'default_replacement' => $paul->id,
            'assignments' => [$leak->id => '', $aircon->id => '', $socket->id => $marc->id],
        ])->assertRedirect(route('users.index'))->assertSessionHas('success');

        $this->assertSame($paul->id, $leak->fresh()->assigned_to);
        $this->assertSame($paul->id, $aircon->fresh()->assigned_to);
        $this->assertSame($marc->id, $socket->fresh()->assigned_to);
        $this->assertFalse($this->leaving->fresh()->is_active);

        Notification::assertSentToTimes($paul, WorkOrderReassignedNotification::class, 2);
        Notification::assertSentToTimes($marc, WorkOrderReassignedNotification::class, 1);
    }

    public function test_without_replacement_work_orders_go_back_to_the_unassigned_queue(): void
    {
        $leak = $this->workOrderFor($this->leaving, 'Fuite');

        $this->actingAs($this->admin)
            ->post(route('users.deactivate.store', $this->leaving), ['default_replacement' => ''])
            ->assertSessionHas('warning');

        $this->assertNull($leak->fresh()->assigned_to);
        $this->assertStringContainsString('à réaffecter', $leak->statusHistories()->latest('id')->value('note'));
    }

    public function test_closed_work_orders_keep_their_original_technician(): void
    {
        $paul = User::factory()->technicien()->create();
        $done = $this->workOrderFor($this->leaving, 'Réparation terminée', 'ferme');

        $this->actingAs($this->admin)->post(route('users.deactivate.store', $this->leaving), ['default_replacement' => $paul->id]);

        // L'historique reste vrai : c'est Jean qui a fait ce travail.
        $this->assertSame($this->leaving->id, $done->fresh()->assigned_to);
    }

    public function test_reassignment_is_traced_in_history_and_activity_log(): void
    {
        $paul = User::factory()->technicien()->create(['name' => 'Paul']);
        $leak = $this->workOrderFor($this->leaving, 'Fuite');

        $this->actingAs($this->admin)->post(route('users.deactivate.store', $this->leaving), ['default_replacement' => $paul->id]);

        $this->assertSame('Réaffecté de Jean à Paul (départ de Jean).', $leak->statusHistories()->latest('id')->value('note'));
        $log = ActivityLog::where('action', 'user.deactivated')->sole();
        $this->assertSame(['reassigned' => 1, 'unassigned' => 0, 'plans' => 0], $log->metadata);
    }

    public function test_running_intervention_timer_is_stopped(): void
    {
        $leak = $this->workOrderFor($this->leaving, 'Fuite', 'en_cours');
        $session = InterventionSession::create(['work_order_id' => $leak->id, 'technician_id' => $this->leaving->id, 'started_at' => now()->subHour()]);

        $this->actingAs($this->admin)->post(route('users.deactivate.store', $this->leaving), []);

        $this->assertNotNull($session->fresh()->ended_at);
    }

    // ===== Garde-fous =====

    public function test_replacement_must_be_an_active_technician_other_than_the_leaver(): void
    {
        $gone = User::factory()->technicien()->create(['is_active' => false]);
        $receptionist = User::factory()->reception()->create();
        $leak = $this->workOrderFor($this->leaving, 'Fuite');

        foreach ([$gone->id, $receptionist->id, $this->leaving->id] as $invalid) {
            $this->actingAs($this->admin)
                ->post(route('users.deactivate.store', $this->leaving), ['default_replacement' => $invalid])
                ->assertSessionHasErrors('default_replacement');
        }

        $this->assertTrue($this->leaving->fresh()->is_active);
        $this->assertSame($this->leaving->id, $leak->fresh()->assigned_to);
    }

    public function test_quick_deactivate_redirects_to_reassignment_when_work_is_open(): void
    {
        $this->workOrderFor($this->leaving, 'Fuite');

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->leaving))
            ->assertRedirect(route('users.deactivate', $this->leaving));

        $this->assertTrue($this->leaving->fresh()->is_active);
    }

    public function test_unchecking_active_box_redirects_to_reassignment_when_work_is_open(): void
    {
        $this->workOrderFor($this->leaving, 'Fuite');

        $this->actingAs($this->admin)->put(route('users.update', $this->leaving), [
            'name' => 'Jean Nouveau-Nom',
            'email' => $this->leaving->email,
            'role' => 'technicien',
            'is_active' => null,
        ])->assertRedirect(route('users.deactivate', $this->leaving));

        $this->assertTrue($this->leaving->fresh()->is_active);
        $this->assertSame('Jean Nouveau-Nom', $this->leaving->fresh()->name);
    }

    public function test_user_without_open_work_is_deactivated_directly(): void
    {
        $this->actingAs($this->admin)->delete(route('users.destroy', $this->leaving))->assertSessionHas('success');

        $this->assertFalse($this->leaving->fresh()->is_active);
    }

    public function test_deactivation_page_lists_open_work_orders(): void
    {
        $this->workOrderFor($this->leaving, 'Fuite chambre 312');
        User::factory()->technicien()->create(['name' => 'Paul']);

        $this->actingAs($this->admin)->get(route('users.deactivate', $this->leaving))
            ->assertOk()
            ->assertSee('Fuite chambre 312')
            ->assertSee('Paul — 0 OT en cours');
    }

    public function test_manager_cannot_deactivate_users(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('users.deactivate', $this->leaving))
            ->assertForbidden();
    }

    // ===== Techniciens partis absents des listes =====

    public function test_departed_technician_is_not_offered_nor_accepted_on_new_work_orders(): void
    {
        $gone = User::factory()->technicien()->create(['name' => 'Technicien Parti', 'is_active' => false]);

        $this->actingAs($this->admin)->get(route('work-orders.create'))->assertDontSee('Technicien Parti');

        $this->actingAs($this->admin)->post(route('work-orders.store'), [
            'title' => 'Test',
            'assigned_to' => $gone->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
        ])->assertSessionHasErrors('assigned_to');
    }

    public function test_old_work_order_can_still_be_edited_with_its_departed_assignee(): void
    {
        $leak = $this->workOrderFor($this->leaving, 'Fuite', 'ferme');
        $this->leaving->update(['is_active' => false]);

        $this->actingAs($this->admin)->put(route('work-orders.update', $leak), [
            'title' => 'Fuite (titre corrigé)',
            'assigned_to' => $this->leaving->id,
            'type_id' => $leak->type_id,
            'priority_id' => $leak->priority_id,
        ])->assertSessionHasNoErrors();
    }
}
