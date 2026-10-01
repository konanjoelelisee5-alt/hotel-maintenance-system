<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sur téléphone la sidebar est masquée : le panneau « Menu » doit offrir les
 * mêmes écrans, sinon l'admin d'astreinte n'atteint ni le journal ni les SLA.
 */
class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_menu_offers_the_administration_screens(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="mobile-menu"', false)
            ->getContent();

        $start = strpos($html, 'id="mobile-menu"');
        $mobileMenu = substr($html, $start, strpos($html, '</aside>', $start) - $start);

        foreach (['activity-logs.index', 'sla-policies.index', 'on-call.edit', 'reports.index', 'users.index'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $mobileMenu, $route);
        }
    }

    public function test_menu_entry_stays_active_on_sub_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->technicien()->create();

        $html = $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('users.index'), '#').'"\s+aria-current="page"#', $html);
    }
}
