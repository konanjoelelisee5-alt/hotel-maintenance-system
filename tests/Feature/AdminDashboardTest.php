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
 * Supervision admin : des indicateurs qui demandent une décision (et ouvrent le bon
 * onglet), plus les alertes que seul l'administrateur peut traiter.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkOrder(string $status, string $priority = 'moyenne', array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => "OT {$status} {$priority}",
            'reported_by' => User::factory()->reception()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', $priority)->firstOrFail()->id,
            'status' => $status,
            ...$attributes,
        ]);
    }

    private function pulse(User $admin, array $query = []): array
    {
        return collect($this->actingAs($admin)->get(route('admin.dashboard', $query))->assertOk()->viewData('pulse'))
            ->mapWithKeys(fn (array $p) => [$p['filter'] => $p['value']])
            ->all();
    }

    public function test_indicators_count_only_work_still_waiting_for_a_decision(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0700000000']);
        $this->makeWorkOrder('ouvert', 'urgente');
        $this->makeWorkOrder('ferme', 'urgente', ['sla_breached' => true]);   // réglé : ne compte plus
        $this->makeWorkOrder('en_cours', 'moyenne', ['sla_breached' => true]);
        $this->makeWorkOrder('resolu');
        $this->makeWorkOrder('en_attente');

        $pulse = $this->pulse($admin);

        $this->assertSame(1, $pulse['urgent']);
        $this->assertSame(3, $pulse['unassigned']);
        $this->assertSame(1, $pulse['late']);
        $this->assertSame(1, $pulse['to_review']);
        $this->assertSame(1, $pulse['waiting']);
    }

    public function test_each_indicator_opens_a_matching_tab(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0700000000']);
        $this->makeWorkOrder('resolu');
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        // Chaque repère ouvre Ordres de travail sur l'onglet correspondant (pas de double liste à l'accueil).
        $tabs = collect($response->viewData('filters'))->pluck('key');
        foreach (collect($response->viewData('pulse'))->whereNotNull('filter') as $p) {
            $this->assertContains($p['filter'], $tabs);
            $response->assertSee('href="'.route('work-orders.index', ['filter' => $p['filter']]).'"', false);
        }

        $this->actingAs($admin)->get(route('work-orders.index', ['filter' => 'to_review']))
            ->assertSee('Ordres à contrôler')
            ->assertSee('OT resolu moyenne');
    }

    public function test_system_alerts_point_to_what_the_admin_must_fix(): void
    {
        $admin = User::factory()->admin()->create(['phone' => null, 'receives_maintenance_alerts' => true]);
        User::factory()->technicien()->create(['must_change_password' => true]);

        $alerts = collect($this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Alertes système')->viewData('systemAlerts'));

        $this->assertTrue($alerts->contains(fn ($a) => str_contains($a['label'], "sans téléphone") && $a['url'] === route('on-call.edit')));
        $this->assertTrue($alerts->contains(fn ($a) => str_contains($a['label'], 'mot de passe provisoire')));
    }

    public function test_system_alerts_are_hidden_when_everything_is_fine(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0700000000']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('Alertes système');
    }

    // ===== Pilotage manager : mêmes indicateurs de décision =====

    public function test_manager_indicators_match_their_tabs_and_ignore_settled_work(): void
    {
        $manager = User::factory()->manager()->create();
        $this->makeWorkOrder('ouvert', 'urgente');
        $this->makeWorkOrder('ferme', 'urgente', ['sla_breached' => true]);
        $this->makeWorkOrder('resolu');
        $this->makeWorkOrder('en_attente');

        $response = $this->actingAs($manager)->get(route('manager.dashboard'))->assertOk();
        $pulse = collect($response->viewData('pulse'));
        $tabs = collect($response->viewData('filters'))->pluck('key');

        $byFilter = $pulse->whereNotNull('filter')->mapWithKeys(fn ($p) => [$p['filter'] => $p['value']]);
        $this->assertSame(1, $byFilter['urgent']);
        $this->assertSame(0, $byFilter['late']);
        $this->assertSame(1, $byFilter['to_review']);
        $this->assertSame(1, $byFilter['waiting']);
        foreach ($byFilter->keys() as $filter) {
            $this->assertContains($filter, $tabs);
        }

        // La carte stock ne filtre pas la file : elle mène à la page des pièces.
        $this->assertSame(route('parts.index', ['tab' => 'low']), $pulse->firstWhere('label', 'Stock sous le seuil')['url']);
    }

    public function test_manager_review_tab_has_its_own_title(): void
    {
        $this->makeWorkOrder('resolu');

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('manager.dashboard', ['filter' => 'to_review']))
            ->assertSee('Ordres à contrôler')
            ->assertSee('OT resolu moyenne');
    }

    public function test_dashboard_has_no_chrome_style_activity_tabs(): void
    {
        // Retiré à la demande de l'utilisateur (jugé encombrant) : l'activité se lit dans la fiche OT.
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('activity-tabs', false);
    }

    public function test_admin_activity_lives_in_settings_not_on_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0700000000']);
        ActivityLog::record('sla_policy.updated', 'Modification de la politique SLA « Urgente »', null, [], $admin);

        // Déjà dans Paramètres › Journal d'activité : pas répété sur l'accueil.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertDontSee('Activité administrative')
            ->assertDontSee('Modification de la politique SLA');

        $this->actingAs($admin)->get(route('activity-logs.index'))
            ->assertSee('Modification de la politique SLA');
    }
}
