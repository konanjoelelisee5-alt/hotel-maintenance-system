<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SlaPolicy;
use App\Models\User;
use App\Notifications\SensitiveAdminActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    private function slaPolicy(): SlaPolicy
    {
        return SlaPolicy::create([
            'name' => 'Urgente',
            'priority' => 'urgente',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 240,
            'is_active' => true,
        ]);
    }

    // ===== Journal du paramétrage =====

    public function test_sla_change_is_logged_with_old_and_new_values(): void
    {
        $policy = $this->slaPolicy();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('sla-policies.update', $policy), [
            'name' => 'Urgente',
            'priority' => 'urgente',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 240,
            'is_active' => '1',
        ]);

        $log = ActivityLog::where('action', 'sla_policy.updated')->sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['response_time_minutes' => ['from' => 30, 'to' => 60]], $log->metadata);
        $this->assertStringContainsString('response_time_minutes 30 → 60', $log->description);
    }

    public function test_deactivation_is_logged_as_such(): void
    {
        $policy = $this->slaPolicy();

        $this->actingAs(User::factory()->admin()->create())->delete(route('sla-policies.destroy', $policy));

        $this->assertTrue(ActivityLog::where('action', 'sla_policy.deactivated')->exists());
    }

    public function test_unchanged_save_is_not_logged(): void
    {
        $policy = $this->slaPolicy();
        $this->actingAs(User::factory()->admin()->create());

        $policy->update(['name' => 'Urgente']);

        $this->assertFalse(ActivityLog::where('action', 'like', 'sla_policy.%')->exists());
    }

    public function test_changes_without_logged_in_user_are_not_logged(): void
    {
        // Cas des seeders et migrations : aucun utilisateur connecté.
        $this->slaPolicy()->update(['response_time_minutes' => 45]);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_skill_creation_is_logged(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('skills.store'), ['name' => 'Plomberie']);

        $this->assertTrue(ActivityLog::where('action', 'skill.created')->exists());
    }

    // ===== Contrôle mutuel entre administrateurs =====

    public function test_other_admins_are_notified_of_sla_change_but_not_the_author(): void
    {
        Notification::fake();
        $policy = $this->slaPolicy();
        $author = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $inactiveAdmin = User::factory()->admin()->create(['is_active' => false]);
        $manager = User::factory()->manager()->create();

        $this->actingAs($author);
        $policy->update(['resolution_time_minutes' => 480]);

        Notification::assertSentTo($otherAdmin, SensitiveAdminActionNotification::class);
        Notification::assertNotSentTo([$author, $inactiveAdmin, $manager], SensitiveAdminActionNotification::class);
    }

    public function test_non_sensitive_configuration_change_does_not_notify_admins(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $author = User::factory()->admin()->create();

        $this->actingAs($author)->post(route('skills.store'), ['name' => 'Électricité']);

        Notification::assertNothingSent();
    }

    public function test_creating_an_admin_account_notifies_other_admins(): void
    {
        Notification::fake();
        $author = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($author)->post(route('users.store'), [
            'name' => 'Nouvel Admin',
            'email' => 'nouvel.admin@example.com',
            'role' => 'admin',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ]);

        Notification::assertSentTo($otherAdmin, SensitiveAdminActionNotification::class);
    }

    public function test_creating_a_regular_account_does_not_notify(): void
    {
        Notification::fake();
        $author = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($author)->post(route('users.store'), [
            'name' => 'Agent HK',
            'email' => 'agent.hk@example.com',
            'role' => 'housekeeping',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ]);

        Notification::assertNothingSent();
    }

    public function test_notification_opens_the_activity_log(): void
    {
        $policy = $this->slaPolicy();
        $author = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($author);
        $policy->update(['resolution_time_minutes' => 480]);

        $notification = $otherAdmin->notifications()->sole();
        $this->assertStringContainsString($author->name, $notification->data['message']);

        $this->actingAs($otherAdmin)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('activity-logs.index'));
    }
}
