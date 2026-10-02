<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rapports : montants en francs CFA (jamais en euros), statuts en clair,
 * périodes rapides ; mêmes montants dans les pages d'achat.
 */
class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    private function workOrder(string $status): WorkOrder
    {
        return WorkOrder::create([
            'title' => "OT {$status}",
            'reported_by' => User::factory()->reception()->create()->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => $status,
        ]);
    }

    public function test_reports_page_shows_cfa_francs_and_readable_statuses(): void
    {
        $this->workOrder('en_cours');
        $this->workOrder('en_attente');
        Supplier::factory()->create();
        PurchaseOrder::factory()->create()->update(['total_amount' => 1250000]);

        $response = $this->actingAs(User::factory()->manager()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee("1\u{00A0}250\u{00A0}000\u{00A0}FCFA", false)
            ->assertDontSee('€')
            ->assertSee('En cours')
            ->assertSee('En attente')
            ->assertSee('7 derniers jours')
            ->assertSee('Mois dernier');

        $this->assertSame(['En cours' => 1, 'En attente' => 1], $response->viewData('byStatus')->all());
    }

    public function test_sla_rate_counts_late_orders_even_before_the_scheduler_flags_them(): void
    {
        $onTime = $this->workOrder('ferme');
        $onTime->update(['sla_resolution_due_at' => now()->addDay(), 'completed_at' => now()]);
        $lateOpen = $this->workOrder('en_cours');        // échéance passée, pas encore signalé dépassé
        $lateOpen->update(['sla_resolution_due_at' => now()->subHour(), 'sla_breached' => false]);
        $lateDone = $this->workOrder('resolu');          // réparé, mais après l'échéance
        $lateDone->update(['sla_resolution_due_at' => now()->subDays(2), 'completed_at' => now()->subDay()]);
        $cancelled = $this->workOrder('annule');         // ne compte pas
        $cancelled->update(['sla_resolution_due_at' => now()->subDay()]);

        $response = $this->actingAs(User::factory()->manager()->create())->get(route('reports.index'));

        $this->assertEquals(33.3, $response->viewData('slaRate'));
        $this->assertSame(3, $response->viewData('slaEligibleCount'));
    }

    public function test_quick_period_fills_the_dates_and_keeps_the_other_filters(): void
    {
        $technician = User::factory()->technicien()->create();

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('reports.index', ['technician_id' => $technician->id]))
            ->assertSee(e(route('reports.index', [
                'technician_id' => $technician->id,
                'date_from' => today()->subDays(6)->toDateString(),
                'date_to' => today()->toDateString(),
            ])), false);
    }

    public function test_pdf_export_still_renders(): void
    {
        $this->workOrder('ferme');

        $this->actingAs(User::factory()->manager()->create())
            ->get(route('reports.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_purchase_order_amounts_are_in_cfa_francs(): void
    {
        $manager = User::factory()->manager()->create(); // la fabrique de commande prend un manager existant
        Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create();
        $order->update(['total_amount' => 87500]);

        $this->actingAs($manager)
            ->get(route('purchase-orders.show', $order))
            ->assertOk()
            ->assertSee("87\u{00A0}500\u{00A0}FCFA", false)
            ->assertDontSee('€');
    }
}
