<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\WorkOrderReopenedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Le service demandeur confirme la réparation, ou rouvre l'OT avec un motif.
 */
class RequesterConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = User::factory()->housekeeping()->create();
        $this->technician = User::factory()->technicien()->create();
    }

    private function repaired(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Clim chambre 105',
            'reported_by' => $this->agent->id,
            'assigned_to' => $this->technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'resolu',
            'completed_at' => now()->subHour(),
            ...$attributes,
        ]);
    }

    public function test_requester_sees_the_question_on_the_work_order_and_the_dashboard(): void
    {
        $workOrder = $this->repaired();

        $this->actingAs($this->agent)->get(route('work-orders.show', $workOrder))
            ->assertSee('Le problème est-il réglé ?')
            ->assertSee("Oui, c'est réglé", false);

        $this->actingAs($this->agent)->get(route('housekeeping.dashboard'))
            ->assertSee('À confirmer')
            ->assertSee('Clim chambre 105');
    }

    public function test_confirming_records_who_and_when(): void
    {
        $workOrder = $this->repaired();

        $this->actingAs($this->agent)->post(route('work-orders.confirm', $workOrder))->assertRedirect();

        $workOrder->refresh();
        $this->assertNotNull($workOrder->requester_confirmed_at);
        $this->assertSame($this->agent->id, $workOrder->requester_confirmed_by);
        $this->actingAs($this->agent)->post(route('work-orders.confirm', $workOrder))->assertForbidden();
    }

    public function test_still_broken_reopens_the_work_order_and_warns_maintenance(): void
    {
        Notification::fake();
        $workOrder = $this->repaired();

        $this->actingAs($this->agent)->post(route('work-orders.reopen', $workOrder), ['reason' => "S'arrête au bout d'une heure"])
            ->assertRedirect();

        $workOrder->refresh();
        $this->assertSame('en_cours', $workOrder->status);
        $this->assertNull($workOrder->completed_at);
        $this->assertStringContainsString("S'arrête au bout d'une heure", $workOrder->statusHistories()->latest('id')->first()->note);
        Notification::assertSentTo($this->technician, WorkOrderReopenedNotification::class);
    }

    public function test_reopening_needs_a_reason(): void
    {
        $this->actingAs($this->agent)->post(route('work-orders.reopen', $this->repaired()), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_only_the_requesting_service_and_only_shortly_after_the_repair(): void
    {
        $this->actingAs(User::factory()->housekeeping()->create())
            ->post(route('work-orders.confirm', $this->repaired()))->assertForbidden();
        $this->actingAs($this->technician)
            ->post(route('work-orders.confirm', $this->repaired()))->assertForbidden();
        $this->actingAs($this->agent)
            ->post(route('work-orders.confirm', $this->repaired(['completed_at' => now()->subDays(20)])))->assertForbidden();
        $this->actingAs($this->agent)
            ->post(route('work-orders.confirm', $this->repaired(['status' => 'en_cours', 'completed_at' => null])))->assertForbidden();
    }
}
