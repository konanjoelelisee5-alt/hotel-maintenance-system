<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche OT en onglets (Aperçu, Intervention, Validation, Échanges, Historique) ;
 * pilotage : un bouton d'étape, le reste dans le menu ⋮, l'annulation en dernier.
 */
class WorkOrderTabsTest extends TestCase
{
    use RefreshDatabase;

    private function workOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Clim chambre 105',
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    public function test_supervisor_lands_on_overview_and_technician_on_intervention(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->workOrder(['assigned_to' => $technician->id, 'status' => 'en_cours']);

        $this->actingAs(User::factory()->manager()->create())->get(route('work-orders.show', $workOrder))
            ->assertSee("x-data=\"{ tab: 'apercu' }\"", false)
            ->assertSee('role="tablist"', false);

        $this->actingAs($technician)->get(route('work-orders.show', $workOrder))
            ->assertSee("x-data=\"{ tab: 'intervention' }\"", false)
            ->assertSee('Avancement de mon intervention');
    }

    public function test_validation_tab_only_appears_once_the_repair_is_declared(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('work-orders.show', $this->workOrder()))
            ->assertDontSee('id="tab-validation"', false);

        $this->actingAs($manager)->get(route('work-orders.show', $this->workOrder(['status' => 'resolu'])))
            ->assertSee('id="tab-validation"', false);
    }

    public function test_pilot_shows_one_step_button_and_keeps_rare_actions_in_the_more_menu(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => User::factory()->technicien()->create()->id, 'status' => 'en_cours']);

        $html = $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.show', $workOrder))->getContent();

        $menu = substr($html, strpos($html, 'aria-label="Autres actions sur l&#039;OT"'));
        $this->assertStringContainsString('Réaffecter / replanifier', $menu);
        $this->assertStringContainsString('Mettre en attente…', $menu);
        // L'annulation est la dernière entrée, après le séparateur, en rouge.
        $this->assertGreaterThan(strpos($menu, 'role="separator"'), strpos($menu, "Annuler l'OT…"));
        $this->assertGreaterThan(strpos($menu, 'Mettre en attente…'), strpos($menu, "Annuler l'OT…"));
    }

    public function test_finished_work_order_has_no_pilot_menu(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('work-orders.show', $this->workOrder(['status' => 'annule'])))
            ->assertDontSee('Autres actions sur l&#039;OT', false);
    }

    public function test_take_over_asks_for_confirmation(): void
    {
        $workOrder = $this->workOrder(['assigned_to' => User::factory()->technicien()->create()->id]);

        $this->actingAs(User::factory()->manager()->create())->get(route('work-orders.show', $workOrder))
            ->assertSee('data-confirm-title="Vous charger de cet OT ?"', false);
    }
}
