<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\SchedulerHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerHealthTest extends TestCase
{
    use RefreshDatabase;

    private function state(string $label): string
    {
        return SchedulerHealth::report()->firstWhere('label', $label)['state'];
    }

    public function test_tasks_never_run_are_reported(): void
    {
        $this->assertSame('never', $this->state('Vérification des délais SLA'));
        $this->assertSame('never', $this->state('Génération des OT préventifs'));
    }

    public function test_sla_check_records_its_run(): void
    {
        $this->artisan('work-orders:check-sla')->assertSuccessful();

        $this->assertNotNull(SchedulerHealth::lastRun('sla'));
        $this->assertSame('ok', $this->state('Vérification des délais SLA'));
    }

    public function test_preventive_generation_records_its_run(): void
    {
        $this->artisan('maintenance:generate-preventive-work-orders')->assertSuccessful();

        $this->assertSame('ok', $this->state('Génération des OT préventifs'));
    }

    public function test_sla_check_is_late_after_two_missed_cycles(): void
    {
        $this->artisan('work-orders:check-sla');

        $this->travel(30)->minutes();
        $this->assertSame('ok', $this->state('Vérification des délais SLA'));

        $this->travel(10)->minutes();
        $this->assertSame('late', $this->state('Vérification des délais SLA'));
    }

    public function test_heartbeat_does_not_pollute_activity_log(): void
    {
        $this->artisan('work-orders:check-sla');

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_admin_dashboard_shows_scheduler_status(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tâches automatiques')
            ->assertSee('Jamais exécutée');
    }
}
