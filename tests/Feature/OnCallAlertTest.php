<?php

namespace Tests\Feature;

use App\Contracts\PhoneAlertSender;
use App\Models\ActivityLog;
use App\Models\EscalationRule;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use App\Notifications\PriorityWorkOrderCreatedNotification;
use App\Notifications\SlaEscalationNotification;
use App\Support\OnCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OnCallAlertTest extends TestCase
{
    use RefreshDatabase;

    /** Capture les alertes téléphone au lieu de les écrire dans le journal. */
    private array $sentToPhones = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PhoneAlertSender::class, new class($this->sentToPhones) implements PhoneAlertSender {
            public function __construct(private array &$sent)
            {
            }

            public function send(string $phone, string $message): void
            {
                $this->sent[] = ['phone' => $phone, 'message' => $message];
            }
        });
    }

    private function workOrderPayload(string $priorityCode): array
    {
        return [
            'title' => 'Fuite d\'eau importante',
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', $priorityCode)->firstOrFail()->id,
        ];
    }

    private function team(): array
    {
        return [
            'manager' => User::factory()->manager()->create(['phone' => '+2250700000002']),
            'chef' => User::factory()->admin()->create(['phone' => '+2250700000001']),
            'informatique' => User::factory()->admin()->create(['receives_maintenance_alerts' => false]),
        ];
    }

    // ===== Qui est de garde =====

    public function test_managers_are_on_call_during_the_day(): void
    {
        $team = $this->team();
        $this->travelTo(today()->setTime(10, 0));

        $this->assertEquals([$team['manager']->id], OnCall::recipients()->pluck('id')->all());
    }

    public function test_maintenance_admins_are_on_call_at_night_but_not_it_admin(): void
    {
        $team = $this->team();
        $this->travelTo(today()->setTime(23, 0));

        $this->assertEquals([$team['chef']->id], OnCall::recipients()->pluck('id')->all());
    }

    public function test_hours_come_from_settings(): void
    {
        $this->team();
        Setting::put(OnCall::DAY_START_KEY, '08:00');
        Setting::put(OnCall::DAY_END_KEY, '17:00');

        $this->assertFalse(OnCall::isDayTime(today()->setTime(7, 30)));
        $this->assertTrue(OnCall::isDayTime(today()->setTime(8, 0)));
        $this->assertFalse(OnCall::isDayTime(today()->setTime(17, 0)));
    }

    public function test_falls_back_to_other_team_when_on_call_team_is_empty(): void
    {
        $chef = User::factory()->admin()->create(['phone' => '+2250700000001']);
        User::factory()->manager()->create(['is_active' => false]);
        $this->travelTo(today()->setTime(10, 0));

        $this->assertEquals([$chef->id], OnCall::recipients()->pluck('id')->all());
    }

    // ===== Alerte à la création d'un OT =====

    public function test_urgent_work_order_alerts_on_call_team_by_phone(): void
    {
        $team = $this->team();
        $this->travelTo(today()->setTime(23, 0));

        $this->actingAs(User::factory()->housekeeping()->create(['name' => 'Agent Yao']))
            ->post(route('work-orders.store'), $this->workOrderPayload('urgente'));

        $this->assertCount(1, $this->sentToPhones);
        $this->assertSame('+2250700000001', $this->sentToPhones[0]['phone']);
        $this->assertStringContainsString('URGENTE', $this->sentToPhones[0]['message']);
        $this->assertStringContainsString('Agent Yao', $this->sentToPhones[0]['message']);
        $this->assertSame(1, $team['chef']->notifications()->count());
        $this->assertSame(0, $team['informatique']->notifications()->count());
    }

    public function test_normal_priority_work_order_does_not_alert(): void
    {
        Notification::fake();
        $this->team();

        $this->actingAs(User::factory()->housekeeping()->create())
            ->post(route('work-orders.store'), $this->workOrderPayload('moyenne'));

        Notification::assertNothingSent();
    }

    public function test_reporter_on_call_is_not_alerted_of_own_report(): void
    {
        Notification::fake();
        $team = $this->team();
        $otherManager = User::factory()->manager()->create(['phone' => '+2250700000003']);
        $this->travelTo(today()->setTime(10, 0));

        $this->actingAs($team['manager'])->post(route('work-orders.store'), $this->workOrderPayload('haute'));

        Notification::assertNotSentTo($team['manager'], PriorityWorkOrderCreatedNotification::class);
        Notification::assertSentTo($otherManager, PriorityWorkOrderCreatedNotification::class);
    }

    public function test_alert_without_phone_stays_in_app(): void
    {
        User::factory()->manager()->create(['phone' => null]);
        $this->travelTo(today()->setTime(10, 0));

        $this->actingAs(User::factory()->reception()->create())
            ->post(route('work-orders.store'), $this->workOrderPayload('urgente'));

        $this->assertSame([], $this->sentToPhones);
        $this->assertSame(1, User::where('role', 'manager')->first()->notifications()->count());
    }

    // ===== Escalades =====

    private function lateWorkOrder(string $priorityCode): WorkOrder
    {
        $workOrder = WorkOrder::create([
            'title' => 'Climatisation en panne',
            'reported_by' => User::factory()->reception()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', $priorityCode)->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
        $workOrder->update([
            'sla_response_due_at' => now()->subMinutes(10),
            'sla_resolution_due_at' => now()->addHours(2),
        ]);

        return $workOrder;
    }

    public function test_admin_escalation_skips_it_admin_and_inactive_accounts(): void
    {
        Notification::fake();
        $team = $this->team();
        $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
        EscalationRule::create(['name' => 'Réponse dépassée', 'trigger_type' => 'reponse_depassee', 'offset_minutes' => 0, 'notify_target' => 'admin', 'is_active' => true]);
        $this->lateWorkOrder('moyenne');

        $this->artisan('work-orders:check-sla')->assertSuccessful();

        Notification::assertSentTo($team['chef'], SlaEscalationNotification::class);
        Notification::assertNotSentTo([$team['informatique'], $inactiveAdmin], SlaEscalationNotification::class);
    }

    public function test_on_call_escalation_of_urgent_work_order_goes_to_phone(): void
    {
        $this->team();
        $this->travelTo(today()->setTime(2, 0));
        EscalationRule::create(['name' => 'Réponse dépassée - Astreinte', 'trigger_type' => 'reponse_depassee', 'offset_minutes' => 0, 'notify_target' => 'astreinte', 'is_active' => true]);
        $this->lateWorkOrder('urgente');

        $this->artisan('work-orders:check-sla')->assertSuccessful();

        $this->assertCount(1, $this->sentToPhones);
        $this->assertSame('+2250700000001', $this->sentToPhones[0]['phone']);
        $this->assertStringContainsString('RETARD', $this->sentToPhones[0]['message']);
    }

    // ===== Gestion des comptes et de la page Astreinte =====

    public function test_phone_is_required_for_accounts_receiving_alerts(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('users.store'), [
            'name' => 'Nouveau Manager',
            'email' => 'nouveau.manager@example.com',
            'role' => 'manager',
            'receives_maintenance_alerts' => '1',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ])->assertSessionHasErrors('phone');
    }

    public function test_phone_is_normalized(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('users.store'), [
            'name' => 'Nouveau Manager',
            'email' => 'nouveau.manager@example.com',
            'phone' => '+225 07 07.12-34 56',
            'role' => 'manager',
            'receives_maintenance_alerts' => '1',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+2250707123456', User::where('email', 'nouveau.manager@example.com')->value('phone'));
    }

    public function test_admin_can_change_on_call_hours_and_it_is_logged(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('on-call.edit'))->assertOk();
        $this->actingAs($admin)->put(route('on-call.update'), ['day_start' => '06:30', 'day_end' => '18:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('06:30', OnCall::dayStart());
        $this->assertTrue(ActivityLog::where('action', 'setting.created')->exists());
    }

    public function test_day_end_must_be_after_day_start(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('on-call.update'), ['day_start' => '19:00', 'day_end' => '07:00'])
            ->assertSessionHasErrors('day_end');
    }

    public function test_manager_cannot_change_on_call_hours(): void
    {
        $this->actingAs(User::factory()->manager()->create())->get(route('on-call.edit'))->assertForbidden();
    }
}
