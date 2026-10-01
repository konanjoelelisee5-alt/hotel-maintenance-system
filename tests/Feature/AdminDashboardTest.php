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

        $tabs = collect($response->viewData('filters'))->pluck('key');
        foreach ($response->viewData('pulse') as $p) {
            $this->assertContains($p['filter'], $tabs);
        }

        $this->actingAs($admin)->get(route('admin.dashboard', ['filter' => 'to_review']))
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

    public function test_recent_activity_leaves_out_logins(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '0700000000']);
        ActivityLog::record('auth.login', 'Connexion de quelqu\'un', null, [], $admin);
        ActivityLog::record('sla_policy.updated', 'Modification de la politique SLA « Urgente »', null, [], $admin);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('Modification de la politique SLA')
            ->assertDontSee('Connexion de quelqu');
    }
}
