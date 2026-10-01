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
 * Ce que le journal doit montrer en plus du paramétrage : connexions et échecs,
 * modifications d'OT, exports de rapports — et l'export CSV ne doit pas exécuter
 * de formule glissée dans un titre.
 */
class ActivityLogCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkOrder(array $attributes = []): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite robinet',
            'reported_by' => User::factory()->reception()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
            ...$attributes,
        ]);
    }

    // ===== Connexions =====

    public function test_successful_login_is_logged_with_its_author(): void
    {
        $user = User::factory()->technicien()->create(['email' => 'tech@hotel.ci']);

        $this->post('/login', ['email' => 'tech@hotel.ci', 'password' => 'password']);

        $log = ActivityLog::where('action', 'auth.login')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertNotNull($log->ip_address);
    }

    public function test_failed_logins_and_lockout_are_logged(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'chef@hotel.ci']);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'chef@hotel.ci', 'password' => 'mauvais']);
        }
        $this->post('/login', ['email' => 'inconnu@hotel.ci', 'password' => 'mauvais']);

        $this->assertSame(5, ActivityLog::where('action', 'auth.failed')->where('subject_id', $admin->id)->count());
        $this->assertTrue(ActivityLog::where('action', 'auth.lockout')->where('subject_id', $admin->id)->exists());
        $this->assertTrue(ActivityLog::where('action', 'auth.failed')->whereNull('subject_id')
            ->where('description', 'like', '%inconnu@hotel.ci%')->exists());
    }

    // ===== Ordres de travail =====

    public function test_work_order_edit_is_logged_with_readable_before_and_after(): void
    {
        $manager = User::factory()->manager()->create();
        $technician = User::factory()->technicien()->create(['name' => 'Yao Konan']);
        $workOrder = $this->makeWorkOrder();
        $urgent = WorkOrderPriority::where('code', 'urgente')->firstOrFail();

        $this->actingAs($manager)->put(route('work-orders.update', $workOrder), [
            'title' => 'Fuite robinet',
            'type_id' => $workOrder->type_id,
            'priority_id' => $urgent->id,
            'assigned_to' => $technician->id,
        ])->assertSessionHasNoErrors();

        $log = ActivityLog::where('action', 'work_order.updated')->firstOrFail();
        $this->assertSame($manager->id, $log->user_id);
        $this->assertStringContainsString('→ '.$urgent->label, $log->description);
        $this->assertStringContainsString('technicien (vide) → Yao Konan', $log->description);
        $this->assertSame(['from' => null, 'to' => $technician->id], $log->metadata['assigned_to']);
    }

    public function test_saving_an_unchanged_work_order_is_not_logged(): void
    {
        $workOrder = $this->makeWorkOrder();

        $this->actingAs(User::factory()->manager()->create())->put(route('work-orders.update', $workOrder), [
            'title' => $workOrder->title,
            'type_id' => $workOrder->type_id,
            'priority_id' => $workOrder->priority_id,
        ]);

        $this->assertFalse(ActivityLog::where('action', 'work_order.updated')->exists());
    }

    // ===== Exports =====

    public function test_csv_export_is_logged_and_neutralises_formulas(): void
    {
        $this->makeWorkOrder(['title' => '=HYPERLINK("http://exemple.test","Cliquez")']);
        $this->makeWorkOrder(['title' => '-2+3']);

        $csv = $this->actingAs(User::factory()->manager()->create())
            ->get(route('reports.export.csv'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(';"\'=HYPERLINK(', $csv);
        $this->assertStringContainsString(";'-2+3;", $csv);
        $this->assertTrue(ActivityLog::where('action', 'report.exported')->where('metadata->format', 'CSV')->exists());
    }

    // ===== Écran du journal =====

    public function test_journal_filters_by_date(): void
    {
        $admin = User::factory()->admin()->create();
        ActivityLog::record('report.exported', 'Export ancien', null, [], $admin);
        ActivityLog::query()->update(['created_at' => now()->subDays(10)]);
        ActivityLog::record('report.exported', 'Export récent', null, [], $admin);

        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['date_from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Export récent')
            ->assertDontSee('Export ancien');
    }

    public function test_journal_rejects_an_invalid_date_without_crashing(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('activity-logs.index', ['date_from' => 'pas-une-date']))
            ->assertSessionHasErrors('date_from');
    }
}
