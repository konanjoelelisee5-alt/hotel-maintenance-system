<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Attaques rejouées après l'audit de sécurité : chaque test reproduit un essai d'attaquant
 * et vérifie qu'il échoue.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_spraying_from_one_address_is_blocked(): void
    {
        // Un mot de passe courant essayé une fois sur chaque compte : jamais 5 échecs sur un
        // même compte, mais la limite par adresse finit par bloquer.
        $users = User::factory()->count(LoginRequest::MAX_ATTEMPTS_PER_IP + 1)->create();
        foreach ($users->take(LoginRequest::MAX_ATTEMPTS_PER_IP) as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password-courant'])->assertSessionHasErrors('email');
        }

        // Même le bon mot de passe d'un compte jamais essayé est refusé tant que dure le blocage.
        $this->post('/login', ['email' => $users->last()->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_forgot_password_does_not_reveal_which_accounts_exist(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'personne@exemple.fr']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame(session('status'), $known->getSession()->get('status'));
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/forgot-password', ['email' => "x{$i}@exemple.fr"]);
        }

        $this->post('/forgot-password', ['email' => 'y@exemple.fr'])->assertStatus(429);
    }

    public function test_spoofed_forwarded_for_header_is_ignored_without_trusted_proxy(): void
    {
        // Sans TRUSTED_PROXIES, l'en-tête X-Forwarded-For ne change pas l'adresse vue par
        // l'application : impossible de changer d'adresse à chaque essai.
        $this->get('/login', ['X-Forwarded-For' => '6.6.6.6']);

        $this->assertNotSame('6.6.6.6', request()->ip());
    }

    public function test_behind_a_declared_proxy_the_real_visitor_address_is_used(): void
    {
        // Render : TRUSTED_PROXIES=* (lu depuis la configuration, conservée en cache même
        // quand Apache ne transmet pas l'environnement à PHP).
        config(['app.trusted_proxies' => '*']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->get('/login', ['X-Forwarded-For' => '41.202.1.1', 'X-Forwarded-Proto' => 'https'])
            ->assertHeader('Strict-Transport-Security');
        $this->assertSame('41.202.1.1', request()->ip());

        \Illuminate\Http\Middleware\TrustProxies::flushState();
    }

    public function test_pages_carry_anti_framing_and_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_links_use_the_official_address_in_production_whatever_the_host_header(): void
    {
        // Empoisonnement du lien « mot de passe oublié » par un en-tête Host inventé.
        $this->app['env'] = 'production';
        config(['app.url' => 'https://hotel.example']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://hotel.example/', URL::route('password.reset', ['token' => 'abc']));
        URL::forceRootUrl(null);
    }

    public function test_comment_with_script_is_displayed_as_text(): void
    {
        $admin = User::factory()->admin()->create();
        $workOrder = WorkOrder::factory()->create();
        WorkOrderComment::create(['work_order_id' => $workOrder->id, 'user_id' => $admin->id, 'content' => '<script>alert("xss")</script>']);

        $this->actingAs($admin)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_array_in_search_filters_does_not_crash_lists(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['parts.index', 'purchase-orders.index', 'rooms.index', 'suppliers.index', 'users.index'] as $route) {
            $this->actingAs($admin)->get(route($route, ['search' => ['x']]))->assertOk();
        }
        $this->actingAs($admin)->get(route('work-orders.index', ['status' => ['x'], 'priority_id' => ['1']]))->assertOk();
        // Tri inconnu (texte d'injection) : tri par défaut, pas d'erreur.
        $this->actingAs($admin)->get(route('work-orders.index', ['sort' => 'created;DROP TABLE users', 'q' => "' OR 1=1 --"]))->assertOk();
    }

    public function test_priority_color_must_be_a_real_color_code(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('work-order-priorities.store'), [
            'code' => 'piege', 'label' => 'Piège', 'color' => 'red;x:',
        ])->assertSessionHasErrors('color');
    }

    public function test_private_files_have_no_public_storage_route(): void
    {
        $this->get('/storage/work-orders/1/photo.jpg')->assertNotFound();
    }
}
