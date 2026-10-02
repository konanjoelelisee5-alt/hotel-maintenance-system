<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liste des OT au style de la Supervision : indicateurs cliquables, onglets
 * avec compteurs, affinage par statut/priorité, SLA lisible pour les OT terminés.
 */
class WorkOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->manager()->create();
    }

    private function workOrder(string $title, array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => $title,
            'reported_by' => User::factory()->reception()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    public function test_late_tab_counts_overdue_orders_before_the_scheduler_flags_them(): void
    {
        $this->workOrder('Fuite lavabo')->update(['sla_resolution_due_at' => now()->subHour(), 'sla_breached' => false]);
        $this->workOrder('Ampoule couloir')->update(['sla_resolution_due_at' => now()->addDay()]);
        $this->workOrder('Clim terminée')->update(['status' => 'ferme', 'sla_resolution_due_at' => now()->subDay(), 'completed_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->manager)->get(route('work-orders.index', ['filter' => 'late']))
            ->assertOk()
            ->assertSee('Ordres en retard SLA')
            ->assertSee('Fuite lavabo')
            ->assertDontSee('Ampoule couloir')
            ->assertDontSee('Clim terminée');

        $this->assertSame(1, $response->viewData('filterCounts')['late']);
        $this->assertSame(3, $response->viewData('filterCounts')['all']);
    }

    public function test_finished_orders_say_whether_they_met_their_deadline(): void
    {
        $this->workOrder('Clim réparée à temps')->update(['status' => 'ferme', 'sla_resolution_due_at' => now()->subDay(), 'completed_at' => now()->subDays(2)]);

        $this->actingAs($this->manager)->get(route('work-orders.index'))
            ->assertSee('Terminé dans le délai')
            ->assertDontSee('Dépassé de');
    }

    public function test_status_and_priority_refine_the_list(): void
    {
        $this->workOrder('Porte qui grince', ['status' => 'en_cours']);
        $this->workOrder('Robinet neuf', ['status' => 'en_attente']);

        $this->actingAs($this->manager)->get(route('work-orders.index', ['status' => 'en_cours']))
            ->assertSee('Porte qui grince')
            ->assertDontSee('Robinet neuf')
            ->assertSee('Réinitialiser');
    }

    public function test_list_has_the_same_tabs_and_counts_as_the_supervision(): void
    {
        $this->workOrder('Clim réparée', ['status' => 'resolu']);
        $this->workOrder('Pièce commandée', ['status' => 'en_attente']);
        $this->workOrder('Fuite en retard')->update(['sla_resolution_due_at' => now()->subHour()]);
        $this->workOrder('Vieux retard clos', ['status' => 'ferme'])->update(['sla_breached' => true]);
        $admin = User::factory()->admin()->create();

        $list = $this->actingAs($admin)->get(route('work-orders.index', ['filter' => 'to_review']))
            ->assertSee('Ordres à contrôler')
            ->assertSee('Clim réparée')
            ->assertDontSee('Pièce commandée');
        $supervision = $this->actingAs($admin)->get(route('admin.dashboard'));

        foreach (['urgent', 'unassigned', 'late', 'to_review', 'waiting'] as $key) {
            $this->assertSame($supervision->viewData('filterCounts')[$key], $list->viewData('filterCounts')[$key], $key);
        }
        $this->assertSame(1, $list->viewData('filterCounts')['late']);   // le retard clos n'est plus à traiter
        $this->assertSame(1, $list->viewData('filterCounts')['waiting']);
    }

    public function test_unassigned_tab_only_lists_open_orders(): void
    {
        $this->workOrder('Volet bloqué');
        $this->workOrder('Ancien OT annulé', ['status' => 'annule']);

        $this->actingAs($this->manager)->get(route('work-orders.index', ['filter' => 'unassigned']))
            ->assertSee('Volet bloqué')
            ->assertDontSee('Ancien OT annulé');
    }
}
