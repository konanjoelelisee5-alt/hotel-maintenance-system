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

    public function test_admin_menu_is_grouped_by_theme(): void
    {
        $sections = collect(\App\Support\Navigation::forSidebar(User::factory()->admin()->create()));

        $this->assertSame(
            ['Exploitation', 'Stock & achats', 'Référentiels', 'Alertes & SLA', 'Administration'],
            $sections->pluck('title')->all(),
        );
        // Les fournisseurs avaient une page mais aucune entrée de menu.
        $this->assertContains('suppliers.index', array_column($sections->firstWhere('title', 'Stock & achats')['items'], 'route'));
        $this->assertSame(['users.index', 'activity-logs.index'], array_column($sections->firstWhere('title', 'Administration')['items'], 'route'));
    }

    public function test_manager_menu_is_grouped_like_the_admin_one_without_admin_screens(): void
    {
        $manager = User::factory()->manager()->create();
        $sections = collect(\App\Support\Navigation::forSidebar($manager));

        $this->assertSame(['Exploitation', 'Stock & achats', 'Référentiels'], $sections->pluck('title')->all());

        // Chaque entrée doit être une page que le manager a le droit d'ouvrir.
        foreach ($sections->pluck('items')->flatten(1) as $item) {
            $this->actingAs($manager)->get(route($item['route'], $item['params'] ?? []))->assertOk();
        }
    }

    public function test_technician_menu_keeps_a_single_section(): void
    {
        $sections = collect(\App\Support\Navigation::forSidebar(User::factory()->technicien()->create()));

        $this->assertSame(['Opérations'], $sections->pluck('title')->all());
    }

    public function test_bottom_bar_gives_each_role_its_daily_screens(): void
    {
        $routes = fn (User $user) => array_column(\App\Support\Navigation::forBottomNav($user), 'route');

        // Réception et gouvernante décident des blocages : plus besoin du menu ☰ pour y aller.
        $this->assertContains('room-blocks.index', $routes(User::factory()->reception()->create()));
        $this->assertContains('room-blocks.index', $routes(User::factory()->housekeeping()->create(['is_department_head' => true])));
        $this->assertNotContains('room-blocks.index', $routes(User::factory()->housekeeping()->create()));
        $this->assertContains('planning.index', $routes(User::factory()->admin()->create()));
    }

    public function test_reception_bottom_bar_counts_block_requests_to_decide(): void
    {
        \App\Models\RoomBlock::create([
            'room_id' => \App\Models\Room::factory()->create()->id,
            'status' => \App\Models\RoomBlock::REQUESTED,
            'reason' => 'Clim en panne',
            'requested_by' => User::factory()->manager()->create()->id,
        ]);

        $this->actingAs(User::factory()->reception()->create())
            ->get(route('reception.dashboard'))
            ->assertSee('aria-label="1 en attente"', false);
    }

    public function test_every_bottom_bar_entry_opens_for_its_role(): void
    {
        $users = [
            User::factory()->admin()->create(),
            User::factory()->manager()->create(),
            User::factory()->technicien()->create(),
            User::factory()->housekeeping()->create(['is_department_head' => true]),
            User::factory()->reception()->create(),
        ];

        foreach ($users as $user) {
            foreach (\App\Support\Navigation::forBottomNav($user) as $item) {
                $this->actingAs($user)->get(route($item['route'], $item['params'] ?? []))
                    ->assertOk();
            }
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
