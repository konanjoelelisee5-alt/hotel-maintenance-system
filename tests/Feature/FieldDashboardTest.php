<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accueils des rôles de terrain au style de la Supervision : bande d'indicateurs,
 * onglets avec compteurs, et des files qui ne montrent que le travail à faire.
 */
class FieldDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function workOrder(User $technician, string $title, string $status): WorkOrder
    {
        return WorkOrder::create([
            'title' => $title,
            'reported_by' => User::factory()->housekeeping()->create()->id,
            'assigned_to' => $technician->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'urgente')->firstOrFail()->id,
            'status' => $status,
        ]);
    }

    public function test_technician_urgent_queue_only_shows_open_work(): void
    {
        $technician = User::factory()->technicien()->create();
        $this->workOrder($technician, 'Fuite à réparer', 'en_cours');
        $this->workOrder($technician, 'Vieille urgence fermée', 'ferme');

        $response = $this->actingAs($technician)->get(route('technicien.dashboard', ['filter' => 'urgent']))
            ->assertOk()
            ->assertSee('Fuite à réparer')
            ->assertDontSee('Vieille urgence fermée');

        $this->assertSame(1, $response->viewData('filterCounts')['urgent']);
    }

    public function test_manager_and_admin_dashboards_share_the_same_building_blocks(): void
    {
        foreach ([User::factory()->manager()->create(), User::factory()->admin()->create()] as $user) {
            $html = $this->actingAs($user)->get(route($user->dashboardRoute()))->assertOk()->getContent();

            // Recherche + nouvel ordre en en-tête, bande d'indicateurs, onglets à compteurs.
            $this->assertStringContainsString('placeholder="Rechercher un ordre, une chambre…"', $html, $user->role->value);
            $this->assertStringContainsString('<title>Tableau de bord · ', $html);
            $this->assertStringContainsString('aria-label="Filtres"', $html);
        }

        $this->actingAs(User::factory()->manager()->create())->get(route('manager.dashboard'))
            ->assertSee('Achats, stock et préventif à suivre')
            ->assertDontSee('Blocages à décider');
    }

    public function test_indicator_colors_come_from_the_controller(): void
    {
        $response = $this->actingAs(User::factory()->technicien()->create())->get(route('technicien.dashboard'));

        foreach ($response->viewData('pulse') as $indicator) {
            $this->assertArrayHasKey('color', $indicator, $indicator['label']);
        }
    }
}
