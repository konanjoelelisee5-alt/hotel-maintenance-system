<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chrono oublié : le technicien saisit un temps après coup ou corrige une de ses
 * sessions ; c'est signalé au responsable et tracé au journal.
 */
class InterventionSessionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $technician;
    private WorkOrder $workOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->technician = User::factory()->technicien()->create();
        $this->workOrder = WorkOrder::create([
            'title' => 'Fuite lavabo',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'assigned_to' => $this->technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'en_cours',
        ]);
    }

    private function period(int $hoursAgo, int $minutes): array
    {
        $start = now()->subHours($hoursAgo)->startOfMinute();

        return ['started_at' => $start->format('Y-m-d\TH:i'), 'ended_at' => $start->copy()->addMinutes($minutes)->format('Y-m-d\TH:i')];
    }

    public function test_forgotten_time_can_be_added_and_is_flagged(): void
    {
        $this->actingAs($this->technician)
            ->post(route('work-orders.sessions.store', $this->workOrder), $this->period(5, 90))
            ->assertSessionHasNoErrors();

        $session = $this->workOrder->interventionSessions()->firstOrFail();
        $this->assertSame(90, $session->duration_minutes);
        $this->assertTrue($session->is_manual);
        $this->assertTrue(ActivityLog::where('action', 'intervention_session.added')->exists());
    }

    public function test_a_timer_left_running_can_be_closed_with_the_real_end(): void
    {
        $session = $this->workOrder->interventionSessions()->create(['technician_id' => $this->technician->id, 'started_at' => now()->subHours(9)]);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.sessions.update', [$this->workOrder, $session]), $this->period(9, 45))
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertSame(45, $session->duration_minutes);
        $this->assertNotNull($session->corrected_at);
        $this->assertNull($this->workOrder->activeSession());
    }

    public function test_periods_are_checked(): void
    {
        $this->actingAs($this->technician)->post(route('work-orders.sessions.store', $this->workOrder), [
            'started_at' => now()->subHour()->format('Y-m-d\TH:i'), 'ended_at' => now()->subHours(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('ended_at');

        $this->actingAs($this->technician)->post(route('work-orders.sessions.store', $this->workOrder), $this->period(20, 13 * 60))
            ->assertSessionHasErrors('ended_at');

        $this->actingAs($this->technician)->post(route('work-orders.sessions.store', $this->workOrder), $this->period(5, 60));
        $this->actingAs($this->technician)->post(route('work-orders.sessions.store', $this->workOrder), $this->period(5, 30))
            ->assertSessionHasErrors('started_at');
    }

    public function test_nobody_corrects_someone_else_s_time(): void
    {
        $session = $this->workOrder->interventionSessions()->create([
            'technician_id' => User::factory()->technicien()->create()->id,
            'started_at' => now()->subHours(3), 'ended_at' => now()->subHours(2), 'duration_minutes' => 60,
        ]);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.sessions.update', [$this->workOrder, $session]), $this->period(3, 30))
            ->assertForbidden();
    }

    public function test_supervisor_sees_the_times_with_manual_entries_flagged(): void
    {
        $this->actingAs($this->technician)->post(route('work-orders.sessions.store', $this->workOrder), $this->period(5, 90));

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.show', $this->workOrder))
            ->assertSee('saisi à la main')
            ->assertDontSee('Démarrer le chrono');
    }
}
