<?php

namespace Tests\Feature;

use App\Models\Part;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiches Pièce, Bon de commande et Fournisseur : écrans refaits, et circuit d'achat
 * complet (une ligne reliée à une pièce alimente le stock à la réception).
 */
class PurchasingScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->manager()->create();
        $this->supplier = Supplier::factory()->create(['name' => 'Froid Services']);
    }

    private function part(array $attributes = []): Part
    {
        return Part::create(['sku' => 'CMP-001', 'name' => 'Compresseur', 'unit' => 'unité', 'quantity_on_hand' => 2,
            'quantity_reserved' => 0, 'reorder_threshold' => 3, 'unit_cost' => 85000, 'is_active' => true, ...$attributes]);
    }

    public function test_receiving_a_line_linked_to_a_part_adds_it_to_stock(): void
    {
        $part = $this->part();

        $this->actingAs($this->manager)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [['part_id' => $part->id, 'description' => 'Compresseur', 'quantity' => 4, 'unit_price' => 85000]],
        ])->assertSessionHasNoErrors();

        $order = PurchaseOrder::latest('id')->firstOrFail();
        $item = $order->items()->firstOrFail();
        $this->assertSame($part->id, $item->part_id);

        $this->actingAs($this->manager)->post(route('purchase-orders.reception.store', $order), ['received' => [$item->id => 4]]);

        $this->assertSame(6, $part->fresh()->quantity_on_hand);
        $this->assertSame('receptionnee', $order->fresh()->status);
    }

    public function test_reception_ignores_lines_of_another_order(): void
    {
        $this->actingAs($this->manager)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id, 'order_date' => now()->toDateString(),
            'items' => [['description' => 'Joint', 'quantity' => 1, 'unit_price' => 500]],
        ]);
        $this->actingAs($this->manager)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id, 'order_date' => now()->toDateString(),
            'items' => [['description' => 'Vanne', 'quantity' => 1, 'unit_price' => 900]],
        ]);
        [$first, $second] = PurchaseOrder::orderBy('id')->get();
        $otherItem = $second->items()->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('purchase-orders.reception.store', $first), ['received' => [$otherItem->id => 1]])
            ->assertNotFound();
        $this->assertSame(0, $otherItem->fresh()->received_quantity);
    }

    public function test_detail_screens_render_with_their_actions(): void
    {
        $part = $this->part();
        $this->actingAs($this->manager); // un mouvement est enregistré au nom de son auteur
        $part->recordMovement('sortie', 1);

        $this->actingAs($this->manager)->get(route('parts.show', $part))
            ->assertOk()
            ->assertSee('Sous le seuil d’alerte')
            ->assertSee('−1 unité')
            ->assertSee('data-confirm-title="Retirer cette pièce du catalogue ?"', false);

        $this->actingAs($this->manager)->get(route('suppliers.show', $this->supplier))
            ->assertOk()
            ->assertSee('href="'.route('purchase-orders.create', ['supplier_id' => $this->supplier->id]).'"', false);

        $this->actingAs($this->manager)->get(route('purchase-orders.create', ['supplier_id' => $this->supplier->id, 'part_id' => $part->id]))
            ->assertOk()
            ->assertSee('Récapitulatif');

        $this->actingAs($this->manager)->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Froid Services');
    }

    public function test_purchase_order_page_offers_the_next_step(): void
    {
        $this->actingAs($this->manager)->post(route('purchase-orders.store'), [
            'supplier_id' => $this->supplier->id, 'order_date' => now()->toDateString(),
            'items' => [['description' => 'Joint', 'quantity' => 1, 'unit_price' => 500]],
        ]);
        $order = PurchaseOrder::latest('id')->firstOrFail();

        $this->actingAs($this->manager)->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertSee('Marquer comme envoyée')
            ->assertSee('data-confirm-title="Annuler ce bon de commande ?"', false);
    }
}
