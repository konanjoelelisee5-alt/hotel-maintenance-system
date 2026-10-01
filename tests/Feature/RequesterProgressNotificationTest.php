<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\WorkOrderProgressNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequesterProgressNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private User $head;
    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->agent = User::factory()->housekeeping()->create();
        $this->head = User::factory()->housekeeping()->create(['is_department_head' => true]);
        $this->technician = User::factory()->technicien()->create(['name' => 'Koffi']);
    }

    private function reported(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite lavabo',
            'room_id' => Room::create(['number' => '214', 'floor' => '2'])->id,
            'reported_by' => $this->agent->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    private function assertStepSentTo(User $user, string $step): void
    {
        Notification::assertSentTo($user, WorkOrderProgressNotification::class,
            fn (WorkOrderProgressNotification $n) => $n->toArray($user)['step'] === $step);
    }

    public function test_agent_and_head_are_told_when_a_technician_is_scheduled(): void
    {
        $workOrder = $this->reported();

        $this->actingAs(User::factory()->manager()->create())->post(route('work-orders.schedule.store', $workOrder), [
            'assigned_to' => $this->technician->id,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i'),
            'estimated_duration_minutes' => 60,
        ]);

        $this->assertStepSentTo($this->agent, WorkOrderProgressNotification::ASSIGNED);
        $this->assertStepSentTo($this->head, WorkOrderProgressNotification::ASSIGNED);

        $message = (new WorkOrderProgressNotification($workOrder->fresh()->load('room', 'assignee'), WorkOrderProgressNotification::ASSIGNED))->toArray($this->agent)['message'];
        $this->assertStringContainsString('Chambre 214', $message);
        $this->assertStringContainsString('Koffi', $message);
    }

    public function test_agent_and_head_are_told_when_it_is_repaired(): void
    {
        $workOrder = $this->reported(['assigned_to' => $this->technician->id]);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.status.update', $workOrder), ['status' => 'resolu'])
            ->assertSessionHasNoErrors();

        $this->assertStepSentTo($this->agent, WorkOrderProgressNotification::RESOLVED);
        $this->assertStepSentTo($this->head, WorkOrderProgressNotification::RESOLVED);
        Notification::assertNotSentTo($this->technician, WorkOrderProgressNotification::class);
    }

    public function test_direct_closure_counts_as_repaired_but_closing_after_resolution_does_not_repeat(): void
    {
        // Mise à jour par le modèle : l'observateur réagit au changement de statut
        // quel que soit le chemin (la fermeture se fait désormais par le contrôle qualité).
        $this->actingAs(User::factory()->manager()->create());
        $direct = $this->reported();
        $direct->update(['status' => 'ferme']);
        Notification::assertSentToTimes($this->agent, WorkOrderProgressNotification::class, 1);

        $alreadyResolved = WorkOrder::create([...$direct->only(['title', 'reported_by', 'type_id', 'priority_id']), 'status' => 'resolu']);
        $alreadyResolved->update(['status' => 'ferme']);
        Notification::assertSentToTimes($this->agent, WorkOrderProgressNotification::class, 1);
    }

    public function test_other_departments_heads_and_the_actor_are_not_notified(): void
    {
        $receptionHead = User::factory()->reception()->create(['is_department_head' => true]);
        // La gouvernante signale elle-même puis fait avancer l'OT : elle n'est pas prévenue de son propre geste.
        $workOrder = $this->reported(['reported_by' => $this->head->id]);

        $workOrder->update(['assigned_to' => $this->technician->id]);
        Notification::assertSentTo($this->head, WorkOrderProgressNotification::class);

        Notification::fake();
        $this->actingAs($this->head);
        $workOrder->update(['status' => 'resolu']);

        Notification::assertNotSentTo($this->head, WorkOrderProgressNotification::class);
        Notification::assertNotSentTo($this->agent, WorkOrderProgressNotification::class);
        Notification::assertNotSentTo($receptionHead, WorkOrderProgressNotification::class);
    }

    public function test_unrelated_edits_send_nothing(): void
    {
        $workOrder = $this->reported(['assigned_to' => $this->technician->id]);

        $workOrder->update(['title' => 'Fuite lavabo (joint)', 'status' => 'en_cours']);

        Notification::assertNothingSent();
    }
}
