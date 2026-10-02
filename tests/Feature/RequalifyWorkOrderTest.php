<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\EscalationLog;
use App\Models\EscalationRule;
use App\Models\SlaPolicy;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * « Requalifier » (panneau Pilotage) : le formulaire s'ouvre, et changer la priorité
 * ou le type recalcule le délai SLA — sans fausser les escalades ni les OT terminés.
 */
class RequalifyWorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->manager = User::factory()->manager()->create();

        // Urgente : 4 h pour résoudre ; Basse : 3 jours.
        SlaPolicy::create(['name' => 'Urgente', 'priority' => 'urgente', 'response_time_minutes' => 30, 'resolution_time_minutes' => 240, 'is_active' => true]);
        SlaPolicy::create(['name' => 'Basse', 'priority' => 'basse', 'response_time_minutes' => 240, 'resolution_time_minutes' => 3 * 24 * 60, 'is_active' => true]);
    }

    private function priority(string $code): WorkOrderPriority
    {
        return WorkOrderPriority::where('code', $code)->firstOrFail();
    }

    /** OT urgent signalé il y a 5 h : son délai de 4 h est dépassé. */
    private function lateUrgentWorkOrder(array $attributes = []): WorkOrder
    {
        $this->travel(-5)->hours();
        $workOrder = WorkOrder::create([
            'title' => 'Ampoule grillée couloir 3',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'assigned_to' => User::factory()->technicien()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => $this->priority('urgente')->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
        $this->travelBack();
        $workOrder->update(['sla_breached' => true]);

        return $workOrder->fresh();
    }

    private function requalify(WorkOrder $workOrder, string $priority)
    {
        return $this->actingAs($this->manager)->put(route('work-orders.update', $workOrder), [
            'title' => $workOrder->title,
            'type_id' => $workOrder->type_id,
            'priority_id' => $this->priority($priority)->id,
            'assigned_to' => $workOrder->assigned_to,
        ]);
    }

    // ===== Le formulaire s'ouvre (il plantait dès qu'un technicien était affecté) =====

    public function test_requalify_form_opens_for_a_work_order_with_a_technician(): void
    {
        $workOrder = $this->lateUrgentWorkOrder();

        $this->actingAs($this->manager)->get(route('work-orders.edit', $workOrder))
            ->assertOk()
            ->assertSee('Requalifier '.$workOrder->code())
            ->assertSee('recalcule le délai SLA');

        $this->actingAs($this->manager)->get(route('work-orders.edit', $workOrder), ['X-Modal' => '1'])
            ->assertOk()
            ->assertSee('class="modal-form', false);
    }

    public function test_requalify_form_keeps_a_technician_who_has_left(): void
    {
        $workOrder = $this->lateUrgentWorkOrder();
        // Nom fixe : un nom aléatoire avec apostrophe (« O'Kon ») serait échappé autrement.
        $workOrder->assignee->update(['is_active' => false, 'name' => 'Yao Konan']);

        $this->actingAs($this->manager)->get(route('work-orders.edit', $workOrder))
            ->assertOk()
            ->assertSee('Yao Konan (a quitté l&#039;hôtel)', false);
    }

    public function test_requalify_button_opens_in_a_window(): void
    {
        $workOrder = $this->lateUrgentWorkOrder();

        // Dans le menu ⋮ du pilotage, ouvert en fenêtre.
        $html = $this->actingAs($this->manager)->get(route('work-orders.show', $workOrder))->getContent();
        $this->assertMatchesRegularExpression(
            '#href="'.preg_quote(route('work-orders.edit', $workOrder), '#').'" role="menuitem"\s+data-modal#',
            $html,
        );
    }

    // ===== Recalcul du SLA =====

    public function test_lowering_the_priority_recalculates_the_sla_and_clears_the_breach(): void
    {
        $workOrder = $this->lateUrgentWorkOrder();
        $expected = $workOrder->created_at->copy()->addDays(3);

        $this->requalify($workOrder, 'basse')->assertSessionHasNoErrors();

        $workOrder->refresh();
        $this->assertTrue($workOrder->sla_resolution_due_at->equalTo($expected));
        $this->assertFalse($workOrder->sla_breached);
        $this->assertSame('Basse', $workOrder->slaPolicy->name);

        $log = ActivityLog::where('action', 'work_order.sla_recalculated')->firstOrFail();
        $this->assertSame($this->manager->id, $log->user_id);
        $this->assertStringContainsString('priorité Basse', $log->description);
    }

    public function test_raising_the_priority_can_make_the_order_late_at_once(): void
    {
        $workOrder = $this->lateUrgentWorkOrder(['priority_id' => $this->priority('basse')->id]);
        $workOrder->update(['sla_breached' => false]);

        $this->requalify($workOrder->fresh(), 'urgente');

        $this->assertTrue($workOrder->fresh()->sla_breached);
    }

    public function test_finished_work_order_keeps_its_original_sla(): void
    {
        $workOrder = $this->lateUrgentWorkOrder(['status' => 'ferme']);
        $before = $workOrder->sla_resolution_due_at;

        $this->requalify($workOrder, 'basse');

        $this->assertTrue($workOrder->fresh()->sla_resolution_due_at->equalTo($before));
        $this->assertTrue($workOrder->fresh()->sla_breached);
        $this->assertFalse(ActivityLog::where('action', 'work_order.sla_recalculated')->exists());
    }

    public function test_changing_only_the_title_does_not_touch_the_sla(): void
    {
        $workOrder = $this->lateUrgentWorkOrder();

        $this->actingAs($this->manager)->put(route('work-orders.update', $workOrder), [
            'title' => 'Ampoules grillées couloir 3',
            'type_id' => $workOrder->type_id,
            'priority_id' => $workOrder->priority_id,
            'assigned_to' => $workOrder->assigned_to,
        ]);

        $this->assertFalse(ActivityLog::where('action', 'work_order.sla_recalculated')->exists());
        $this->assertTrue($workOrder->fresh()->sla_breached);
    }

    // ===== Escalades =====

    public function test_escalation_fires_again_for_the_new_deadline_but_not_twice_for_the_same(): void
    {
        $rule = EscalationRule::create(['name' => 'Résolution dépassée', 'trigger_type' => 'resolution_depassee', 'offset_minutes' => 0, 'notify_target' => 'manager', 'is_active' => true]);
        $workOrder = $this->lateUrgentWorkOrder();

        $this->artisan('work-orders:check-sla');
        $this->assertSame(1, EscalationLog::where('escalation_rule_id', $rule->id)->count());

        // Requalifié en Basse : nouvelle échéance dans ~3 jours, rien ne part maintenant.
        $this->requalify($workOrder, 'basse');
        $this->artisan('work-orders:check-sla');
        $this->assertSame(1, EscalationLog::where('escalation_rule_id', $rule->id)->count());

        // La nouvelle échéance passe : l'alerte repart, une seule fois.
        $this->travel(4)->days();
        $this->artisan('work-orders:check-sla');
        $this->artisan('work-orders:check-sla');
        $this->assertSame(2, EscalationLog::where('escalation_rule_id', $rule->id)->count());
    }
}
