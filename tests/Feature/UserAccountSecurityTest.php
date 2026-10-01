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

    // ===== Re-saisie du mot de passe =====

    public function test_account_pages_ask_for_the_password_again(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->technicien()->create();
        $this->forgetPasswordConfirmation();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.edit', $user))->assertRedirect(route('password.confirm'));
    }

    public function test_forged_account_creation_without_password_confirmation_is_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $this->forgetPasswordConfirmation();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'role' => 'admin',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ])->assertRedirect(route('password.confirm'));

        $this->assertFalse(User::where('email', 'intrus@example.com')->exists());
    }

    public function test_confirming_the_password_opens_the_account_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->technicien()->create();
        $this->forgetPasswordConfirmation();

        $this->actingAs($admin)->get(route('users.edit', $user));
        $this->actingAs($admin)->post(route('password.confirm'), ['password' => 'password'])
            ->assertRedirect(route('users.edit', $user));
        $this->actingAs($admin)->get(route('users.edit', $user))->assertOk();
    }

    // ===== Identité (nom / e-mail) =====

    public function test_email_change_of_an_admin_is_logged_and_notifies_the_other_admins(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create(['email' => 'chef@hotel.ci']);
        $witness = User::factory()->admin()->create();

        $this->actingAs($actor)
            ->put(route('users.update', $target), $this->updatePayload($target, ['email' => 'pirate@example.com']))
            ->assertSessionHasNoErrors();

        $log = ActivityLog::where('action', 'user.identity_changed')->firstOrFail();
        $this->assertSame(['from' => 'chef@hotel.ci', 'to' => 'pirate@example.com'], $log->metadata['email']);
        $this->assertTrue($witness->notifications()->where('data->activity_log_id', $log->id)->exists());
        $this->assertSame(0, $actor->notifications()->count());
    }

    public function test_name_change_of_a_regular_account_is_logged_without_alert(): void
    {
        $actor = User::factory()->admin()->create();
        $witness = User::factory()->admin()->create();
        $target = User::factory()->technicien()->create(['name' => 'Kouassi Jean']);

        $this->actingAs($actor)
            ->put(route('users.update', $target), $this->updatePayload($target, ['name' => 'Kouassi Jean-Marc']));

        $this->assertTrue(ActivityLog::where('action', 'user.identity_changed')->where('subject_id', $target->id)->exists());
        $this->assertSame(0, $witness->notifications()->count());
    }

    public function test_changing_own_email_requires_current_password_and_is_logged(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'chef@hotel.ci']);
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('profile.update'), ['name' => $admin->name, 'email' => 'autre@hotel.ci'])
            ->assertSessionHasErrors('current_password');
        $this->assertSame('chef@hotel.ci', $admin->fresh()->email);

        $this->actingAs($admin)
            ->patch(route('profile.update'), ['name' => $admin->name, 'email' => 'autre@hotel.ci', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertSame('autre@hotel.ci', $admin->fresh()->email);
        $this->assertTrue(ActivityLog::where('action', 'user.identity_changed')->where('subject_id', $admin->id)->exists());
    }

    public function test_new_account_must_change_the_password_chosen_by_the_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('users.store'), [
            'name' => 'Awa Koné',
            'email' => 'awa@example.com',
            'role' => 'housekeeping',
            'password' => 'Secret-123456',
            'password_confirmation' => 'Secret-123456',
        ]);

        $this->assertTrue(User::where('email', 'awa@example.com')->value('must_change_password'));
    }

    // ===== Premier administrateur / données de démo =====

    public function test_create_admin_command_creates_admin_with_temporary_password(): void
    {
        $this->artisan('admin:create', ['email' => 'chef@hotel.ci', 'name' => 'Chef Maintenance'])
            ->assertSuccessful();

        $admin = User::where('email', 'chef@hotel.ci')->first();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(ActivityLog::where('action', 'user.created')->where('subject_id', $admin->id)->exists());
    }

    public function test_create_admin_command_refuses_an_existing_email(): void
    {
        User::factory()->technicien()->create(['email' => 'chef@hotel.ci']);

        $this->artisan('admin:create', ['email' => 'chef@hotel.ci', 'name' => 'Chef Maintenance'])->assertFailed();

        $this->assertSame(UserRole::Technicien, User::where('email', 'chef@hotel.ci')->value('role'));
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.demo' => false]);

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertSame(0, User::count());
    }
}
