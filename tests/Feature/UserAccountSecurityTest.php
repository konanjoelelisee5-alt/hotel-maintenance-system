<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active ? '1' : null,
        ], $overrides);
    }

    // ===== Dernier administrateur =====

    public function test_admin_cannot_change_own_role_with_forged_request(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), $this->updatePayload($admin, ['role' => 'technicien']))
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_cannot_deactivate_own_account_with_forged_request(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), $this->updatePayload($admin, ['is_active' => null]))
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_is_last_active_admin_only_counts_active_admins(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(['is_active' => false]);
        User::factory()->manager()->create();

        $this->assertTrue($admin->isLastActiveAdmin());

        User::factory()->admin()->create();
        $this->assertFalse($admin->fresh()->isLastActiveAdmin());
    }

    public function test_admin_can_demote_another_admin_when_others_remain(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($actor)
            ->put(route('users.update', $target), $this->updatePayload($target, ['role' => 'manager']))
            ->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Manager, $target->fresh()->role);
    }

    // ===== Journal =====

    public function test_role_change_is_logged(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->technicien()->create();

        $this->actingAs($actor)
            ->put(route('users.update', $target), $this->updatePayload($target, ['role' => 'admin']));

        $log = ActivityLog::where('action', 'user.role_changed')->first();
        $this->assertNotNull($log);
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame(['from' => 'technicien', 'to' => 'admin'], $log->metadata);
    }

    public function test_user_creation_log_is_linked_to_the_new_user(): void
    {
        $actor = User::factory()->admin()->create();

        $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'Awa Koné',
            'email' => 'awa@example.com',
            'role' => 'housekeeping',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ]);

        $created = User::where('email', 'awa@example.com')->first();
        $this->assertSame($created->id, ActivityLog::where('action', 'user.created')->value('subject_id'));
    }

    // ===== Compte désactivé =====

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->technicien()->create();
        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $this->get(route('work-orders.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ===== Réinitialisation du mot de passe =====

    public function test_admin_can_reset_a_user_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->housekeeping()->create();

        $response = $this->actingAs($admin)->post(route('users.password.reset', $user));

        $response->assertRedirect(route('users.index'))->assertSessionHas('temporary_password');
        $temporary = session('temporary_password')['password'];

        $user->refresh();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporary, $user->password));
        $this->assertTrue(ActivityLog::where('action', 'user.password_reset')->exists());
    }

    public function test_non_admin_cannot_reset_a_password(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->technicien()->create();

        $this->actingAs($manager)->post(route('users.password.reset', $user))->assertForbidden();
    }

    public function test_user_with_temporary_password_is_forced_to_change_it(): void
    {
        $user = User::factory()->technicien()->create(['must_change_password' => true]);

        $this->actingAs($user)->get(route('work-orders.index'))->assertRedirect(route('profile.edit'));
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'Nouveau-Secret-2026',
            'password_confirmation' => 'Nouveau-Secret-2026',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('work-orders.index'))->assertOk();
    }

    // ===== Commande de secours =====

    public function test_recover_command_restores_an_admin_account(): void
    {
        $user = User::factory()->manager()->create(['is_active' => false, 'email' => 'chef@example.com']);

        $this->artisan('admin:recover', ['email' => 'chef@example.com'])
            ->expectsConfirmation('Rétablir ce compte comme administrateur avec un nouveau mot de passe temporaire ?', 'yes')
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
    }

    public function test_recover_command_fails_for_unknown_email(): void
    {
        $this->artisan('admin:recover', ['email' => 'inconnu@example.com'])->assertFailed();
    }
}
