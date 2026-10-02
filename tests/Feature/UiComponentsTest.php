<?php

namespace Tests\Feature;

use App\Models\WorkOrder;
use App\Support\Swatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Composants de base de la structure cible : couleurs de statut uniques, menu « Plus »
 * (⋮) avec actions dangereuses à part, confirmation stylée, onglets, bande d'indicateurs.
 */
class UiComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_status_has_one_color_used_by_the_badge(): void
    {
        $this->assertSame(array_keys(WorkOrder::STATUS_LABELS), array_keys(WorkOrder::STATUS_COLORS));

        foreach (WorkOrder::STATUS_COLORS as $status => $color) {
            $html = Blade::render('<x-work-order-status-badge :status="$s" />', ['s' => $status]);
            $this->assertStringContainsString(Swatch::pill($color), $html, $status);
            $this->assertStringContainsString(WorkOrder::STATUS_LABELS[$status], $html);
        }
    }

    public function test_more_menu_separates_danger_actions_and_asks_for_confirmation(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-more-menu>
                <x-more-menu.item href="/modifier" icon="pencil">Modifier</x-more-menu.item>
                <x-more-menu.separator />
                <x-more-menu.item action="/supprimer" method="DELETE" danger confirm="Le lieu ne sera plus proposé." confirm-label="Mettre hors service">Mettre hors service</x-more-menu.item>
            </x-more-menu>
            BLADE);

        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        $this->assertStringContainsString('role="separator"', $html);
        $this->assertStringContainsString('data-confirm="Le lieu ne sera plus proposé."', $html);
        $this->assertStringContainsString('data-confirm-label="Mettre hors service"', $html);
        $this->assertStringContainsString('data-confirm-tone="danger"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        // L'action dangereuse vient après le séparateur.
        $this->assertGreaterThan(strpos($html, 'role="separator"'), strpos($html, 'action="/supprimer"'));
    }

    public function test_tabs_mark_the_active_view_and_show_counts(): void
    {
        $html = Blade::render('<x-tabs :items="$items" active="late" />', ['items' => [
            ['key' => 'all', 'label' => 'Tous', 'count' => 12, 'href' => '/ot'],
            ['key' => 'late', 'label' => 'En retard', 'count' => 3, 'href' => '/ot?filter=late'],
        ]]);

        $this->assertMatchesRegularExpression('#href="/ot\?filter=late"\s+aria-current="page"#', $html);
        $this->assertStringContainsString('>3</span>', $html);
    }

    public function test_kpi_band_links_each_indicator(): void
    {
        $html = Blade::render('<x-kpi-band :items="$items" />', ['items' => [
            ['label' => 'En retard SLA', 'value' => 4, 'sub' => 'délai dépassé', 'dot' => 'bg-red', 'href' => '/ot?filter=late', 'active' => true],
        ]]);

        $this->assertMatchesRegularExpression('#href="/ot\?filter=late"\s+aria-current="true"#', $html);
        $this->assertStringContainsString('délai dépassé', $html);
    }

    public function test_layout_ships_the_confirmation_dialog(): void
    {
        $html = $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('work-orders.index'))->getContent();

        $this->assertStringContainsString('<dialog id="confirm-dialog"', $html);
    }
}
