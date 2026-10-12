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

        // Les écrans de configuration sont regroupés derrière « Paramètres ».
        foreach (['reports.index', 'quality-controls.index', 'rooms.index', 'settings.index'] as $route) {
            $this->assertStringContainsString('href="'.route($route).'"', $mobileMenu, $route);
        }
    }

    public function test_admin_menu_is_grouped_by_theme(): void
    {
        $sections = collect(\App\Support\Navigation::forSidebar(User::factory()->admin()->create()));

        $this->assertSame(
            ['Exploitation', 'Stock & achats', 'Patrimoine', 'Administration'],
            $sections->pluck('title')->all(),
        );
        // Les fournisseurs avaient une page mais aucune entrée de menu.
        $this->assertContains('suppliers.index', array_column($sections->firstWhere('title', 'Stock & achats')['items'], 'route'));
        // Configuration : une seule entrée, qui reste active sur chacun de ses écrans.
        $this->assertSame(['settings.index'], array_column($sections->firstWhere('title', 'Administration')['items'], 'route'));
    }

    public function test_settings_home_lists_every_configuration_screen_for_the_admin_only(): void
    {
        $admin = User::factory()->admin()->create();
        $response = $this->actingAs($admin)->get(route('settings.index'))->assertOk();

        foreach (collect(\App\Support\Navigation::settings())->flatten(1) as $item) {
            $response->assertSee('href="'.route($item['route']).'"', false);
            $this->actingAs($admin)->get(route($item['route']))->assertOk();
        }

        $this->actingAs(User::factory()->manager()->create())->get(route('settings.index'))->assertForbidden();
    }

    public function test_manager_menu_is_grouped_like_the_admin_one_without_admin_screens(): void
    {
        $manager = User::factory()->manager()->create();
        $sections = collect(\App\Support\Navigation::forSidebar($manager));

        $this->assertSame(['Exploitation', 'Stock & achats', 'Patrimoine'], $sections->pluck('title')->all());

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

    public function test_bottom_bar_holds_neither_profile_nor_settings(): void
    {
        foreach ([User::factory()->admin(), User::factory()->manager(), User::factory()->technicien(), User::factory()->housekeeping(), User::factory()->reception()] as $factory) {
            $routes = array_column(\App\Support\Navigation::forBottomNav($factory->create()), 'route');
            $this->assertNotContains('profile.edit', $routes);
            $this->assertNotContains('settings.index', $routes);
        }

        // Admin, manager, technicien : profil et paramètres dans le menu ⋮.
        $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->getContent();
        $start = strpos($html, 'id="more-menu"');
        $moreMenu = substr($html, $start, strpos($html, 'Se déconnecter', $start) - $start);
        $this->assertStringContainsString('href="'.route('profile.edit').'"', $moreMenu);
        $this->assertStringContainsString('href="'.route('settings.index').'"', $moreMenu);
    }

    public function test_long_menus_get_a_blue_scroll_button_instead_of_a_scrollbar(): void
    {
        foreach ([[User::factory()->admin()->create(), 'admin.dashboard'], [User::factory()->manager()->create(), 'manager.dashboard']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route))
                ->assertSee('Aller en bas du menu', false)
                ->assertSee('[scrollbar-width:none]', false);
        }
        // Menu court du technicien : pas de rond, barre normale.
        $this->actingAs(User::factory()->technicien()->create())->get(route('technicien.dashboard'))
            ->assertSee('id="more-menu"', false)
            ->assertDontSee('Aller en bas du menu', false);
    }

    public function test_housekeeping_and_reception_get_a_profile_button_instead_of_a_menu_on_phone(): void
    {
        foreach ([User::factory()->housekeeping()->create(), User::factory()->reception()->create(['is_department_head' => true])] as $user) {
            $html = $this->actingAs($user)->get(route($user->dashboardRoute()))->assertOk()
                ->assertSee('aria-controls="profile-menu"', false)
                ->assertDontSee('aria-controls="more-menu"', false)
                ->getContent();
            $start = strpos($html, 'id="profile-menu"');
            $profileMenu = substr($html, $start, strpos($html, route('release-notes.index'), $start) - $start);
            $this->assertStringContainsString('href="'.route('profile.edit').'"', $profileMenu);
            $this->assertStringContainsString('action="'.route('logout').'"', $profileMenu);
        }

        // Responsable de réception : son bilan reste à un geste, sur l'accueil.
        $this->actingAs($user)->get(route('reception.dashboard'))
            ->assertSee('href="'.route('reception.monthly-report').'"', false);
    }

    public function test_every_page_outside_the_bottom_bar_has_a_way_back_on_phone(): void
    {
        $back = '#<a href="[^"]*" data-back\s+aria-label="Retour"#';
        $close = '#<a href="[^"]*" data-close\s+aria-label="Fermer"#';
        $users = [
            User::factory()->admin()->create(),
            User::factory()->manager()->create(),
            User::factory()->technicien()->create(),
            User::factory()->housekeeping()->create(),
            User::factory()->housekeeping()->create(['is_department_head' => true]),
            User::factory()->reception()->create(['is_department_head' => true]),
        ];

        foreach ($users as $user) {
            $bottom = array_column(\App\Support\Navigation::forBottomNav($user), 'route');
            $menu = collect(\App\Support\Navigation::forSidebar($user))->pluck('items')->flatten(1);

            foreach ($menu as $item) {
                $html = $this->actingAs($user)->get(route($item['route'], $item['params'] ?? []))->assertOk()->getContent();
                $label = "{$user->role->value} : {$item['route']}";
                if (in_array($item['route'], $bottom, true)) {
                    $this->assertDoesNotMatchRegularExpression($back, $html, "Pas de retour sur une page de la barre du bas ({$label}).");
                } else {
                    $this->assertMatchesRegularExpression($back, $html, "Retour manquant ({$label}).");
                }
            }

            // Pages « à côté » : on les ferme pour revenir là où on était.
            foreach (\App\Support\Navigation::ASIDE_ROUTES as $route) {
                $this->assertMatchesRegularExpression($close, $this->actingAs($user)->get(route($route))->assertOk()->getContent(), "Fermer manquant ({$user->role->value} : {$route}).");
            }
        }

        // Le planning d'un technicien, ouvert par le manager, revient au planning de l'équipe.
        $this->actingAs($users[1])->get(route('planning.technician', $users[2]))
            ->assertSee('href="'.route('planning.index').'" data-back', false);
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

    public function test_a_page_has_the_same_name_in_the_menu_the_bottom_bar_and_its_title(): void
    {
        // [rôle, route, nom attendu dans le menu, dans la barre du bas, en titre de page]
        $cases = [
            [User::factory()->admin()->create(), 'admin.dashboard', 'Tableau de bord', 'Accueil', 'Tableau de bord'],
            [User::factory()->manager()->create(), 'manager.dashboard', 'Tableau de bord', 'Accueil', 'Tableau de bord'],
            [User::factory()->manager()->create(), 'quality-controls.index', 'Validation', 'Validation', 'Validation'],
            [User::factory()->technicien()->create(), 'technicien.dashboard', 'Ma journée', 'Ma journée', 'Ma journée'],
            [User::factory()->technicien()->create(), 'work-orders.index', 'Mes ordres', 'Mes ordres', 'Mes ordres'],
            [User::factory()->housekeeping()->create(), 'housekeeping.dashboard', 'Accueil', 'Accueil', 'Accueil'],
            [User::factory()->housekeeping()->create(), 'work-orders.index', 'Mes signalements', 'Signalements', 'Mes signalements'],
            [User::factory()->housekeeping()->create(['is_department_head' => true]), 'work-orders.index', "Signalements de l'équipe", 'Signalements', "Signalements de l'équipe"],
            [User::factory()->reception()->create(), 'reception.dashboard', 'Accueil', 'Accueil', 'Accueil'],
            [User::factory()->reception()->create(), 'work-orders.index', 'Demandes', 'Demandes', 'Demandes'],
            [User::factory()->reception()->create(), 'room-blocks.index', 'Chambres bloquées', 'Blocages', 'Chambres bloquées'],
        ];

        foreach ($cases as [$user, $route, $menu, $bottom, $title]) {
            $menuItems = collect(\App\Support\Navigation::forSidebar($user))->pluck('items')->flatten(1);
            $this->assertSame($menu, $menuItems->firstWhere('route', $route)['label'], "menu {$route}");
            $this->assertSame($bottom, collect(\App\Support\Navigation::forBottomNav($user))->firstWhere('route', $route)['label'], "barre {$route}");

            // Le titre de la page (onglet, barre du haut du téléphone) : chaque coquille met en
            // forme son en-tête à sa façon (la réception accueille par « Bonjour, … »).
            $this->actingAs($user)->get(route($route))
                ->assertSee('<title>'.e($title).' · ', false);
        }
    }

    public function test_menu_entry_stays_active_on_sub_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->technicien()->create();

        $html = $this->actingAs($admin)->get(route('users.edit', $user))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('settings.index'), '#').'"\s+aria-current="page"#', $html);
    }
}
