<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Housekeeping;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Une seule entrée surlignée à la fois : pour chaque rôle, chaque menu (sidebar,
 * barre du bas, rail HK) et chaque page de l'application, au plus une entrée est
 * active, et chaque entrée l'est sur sa propre page.
 */
class NavigationHighlightTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_navigation_entry_is_highlighted_per_page(): void
    {
        $pages = collect(Route::getRoutes()->getRoutesByName())->keys();
        $users = [
            User::factory()->admin()->create(),
            User::factory()->manager()->create(),
            User::factory()->technicien()->create(),
            User::factory()->housekeeping()->create(),
            User::factory()->housekeeping()->create(['is_department_head' => true]),
            User::factory()->reception()->create(),
            User::factory()->reception()->create(['is_department_head' => true]),
        ];

        foreach ($users as $user) {
            $menus = [
                'menu' => collect(Navigation::forSidebar($user))->pluck('items')->flatten(1),
                'barre du bas' => collect(Navigation::forBottomNav($user)),
            ];
            if ($user->role === UserRole::Housekeeping) {
                $menus['rail'] = $menus['barre du bas']->reject(fn ($i) => $i['route'] === 'profile.edit')
                    ->merge($user->isDepartmentHead() ? Housekeeping::headTools() : []);
            }

            foreach ($menus as $menu => $items) {
                foreach ($pages as $page) {
                    $active = $items->filter(fn ($i) => Str::is(Navigation::activePatterns($i), $page))->pluck('label');
                    $this->assertLessThanOrEqual(1, $active->count(),
                        "{$user->role->value}".($user->isDepartmentHead() ? ' (responsable)' : '')." — {$menu}, page {$page} : ".$active->join(' + '));
                }
                foreach ($items as $item) {
                    $this->assertTrue(Str::is(Navigation::activePatterns($item), $item['route']), "« {$item['label']} » n'est pas active sur sa propre page.");
                }
            }
        }
    }

    public function test_governess_sees_only_the_current_tool_highlighted(): void
    {
        $head = User::factory()->housekeeping()->create(['is_department_head' => true]);

        $html = $this->actingAs($head)->get(route('housekeeping.floor-plan'))->assertOk()->getContent();

        // Deux navigations dans la page (sidebar d'ordinateur, rail de tablette) : active dans chacune.
        $this->assertSame(2, preg_match_all('#href="'.preg_quote(route('housekeeping.floor-plan'), '#').'"\s+aria-current="page"#', $html));
        $this->assertDoesNotMatchRegularExpression('#href="'.preg_quote(route('housekeeping.dashboard'), '#').'"\s+aria-current="page"#', $html);
        $this->assertDoesNotMatchRegularExpression('#href="'.preg_quote(route('housekeeping.monthly-report'), '#').'"\s+aria-current="page"#', $html);
    }
}
