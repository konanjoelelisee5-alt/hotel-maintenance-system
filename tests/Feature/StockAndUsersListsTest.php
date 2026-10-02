<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listes Utilisateurs, Pièces et Bons de commande au modèle de la liste des OT :
 * onglets avec compteurs, recherche, et filtres qui se combinent correctement.
 */
class StockAndUsersListsTest extends TestCase
{
    use RefreshDatabase;

    private function part(string $name, int $onHand, int $threshold, bool $active = true): Part
    {
        return Part::create(['sku' => strtoupper(substr($name, 0, 3)).rand(100, 999), 'name' => $name, 'unit' => 'u',
            'quantity_on_hand' => $onHand, 'quantity_reserved' => 0, 'reorder_threshold' => $threshold, 'unit_cost' => 1500, 'is_active' => $active]);
    }

    public function test_low_stock_tab_still_applies_when_searching(): void
    {
        $this->part('Filtre robinet', 1, 5);   // sous le seuil
        $this->part('Filtre clim', 40, 5);     // bien approvisionné

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('parts.index', ['tab' => 'low', 'search' => 'Filtre']))
            ->assertOk()
            ->assertSee('Filtre robinet')
            ->assertDontSee('Filtre clim');
    }

    public function test_retired_parts_have_their_own_tab(): void
    {
        $this->part('Ancienne ampoule', 3, 1, active: false);
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('parts.index'))->assertDontSee('Ancienne ampoule');
        $this->actingAs($manager)->get(route('parts.index', ['tab' => 'inactive']))->assertSee('Ancienne ampoule');
    }

    public function test_users_tabs_count_and_search_by_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->technicien()->create(['name' => 'Kouassi Yao', 'email' => 'kouassi@hotel.ci']);
        User::factory()->technicien()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'kouassi@']))
            ->assertOk()
            ->assertSee('Kouassi Yao');

        $this->assertSame(2, $response->viewData('counts')['technicien']);
        $this->assertSame(1, $response->viewData('counts')['inactive']);
    }

    public function test_purchase_orders_list_renders_with_tabs(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('purchase-orders.index', ['tab' => 'open']))
            ->assertOk()
            ->assertSee('Commandes en cours');
    }
}
