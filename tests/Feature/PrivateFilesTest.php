<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fichiers joints : stockés hors du dossier public, lisibles seulement par qui
 * voit l'OT (ou le bon de commande), et retirés sans être détruits.
 */
class PrivateFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function makeWorkOrder(?User $assignee = null): WorkOrder
    {
        return WorkOrder::create([
            'title' => 'Fuite robinet',
            'reported_by' => User::factory()->reception()->create()->id,
            'assigned_to' => $assignee?->id,
            'type_id' => WorkOrderType::where('code', 'maintenance')->firstOrFail()->id,
            'priority_id' => WorkOrderPriority::where('code', 'moyenne')->firstOrFail()->id,
            'status' => 'ouvert',
        ]);
    }

    private function attach(WorkOrder $workOrder, User $uploader): WorkOrderAttachment
    {
        $this->actingAs($uploader)->post(route('work-orders.attachments.store', $workOrder), [
            'files' => [UploadedFile::fake()->create('devis.pdf', 100, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        return $workOrder->attachments()->latest('id')->firstOrFail();
    }

    // ===== Stockage privé =====

    public function test_attachment_is_stored_outside_the_public_disk(): void
    {
        $technician = User::factory()->technicien()->create();
        $attachment = $this->attach($this->makeWorkOrder($technician), $technician);

        Storage::disk('local')->assertExists($attachment->file_path);
        Storage::disk('public')->assertMissing($attachment->file_path);
        $this->assertStringNotContainsString('/storage/', $attachment->url);
    }

    public function test_guest_cannot_open_an_attachment(): void
    {
        $technician = User::factory()->technicien()->create();
        $attachment = $this->attach($this->makeWorkOrder($technician), $technician);
        $this->app['auth']->forgetGuards();

        $this->get($attachment->url)->assertRedirect(route('login'));
    }

    public function test_assigned_technician_can_open_the_attachment_but_another_cannot(): void
    {
        $technician = User::factory()->technicien()->create();
        $attachment = $this->attach($this->makeWorkOrder($technician), $technician);

        $this->actingAs($technician)->get($attachment->url)->assertOk();
        $this->actingAs(User::factory()->technicien()->create())->get($attachment->url)->assertForbidden();
    }

    public function test_attachment_cannot_be_reached_through_another_work_order(): void
    {
        $technician = User::factory()->technicien()->create();
        $mine = $this->makeWorkOrder($technician);
        $other = $this->makeWorkOrder(User::factory()->technicien()->create());
        $attachment = $this->attach($other, User::factory()->admin()->create());

        // Droit sur « mine », fichier de « other » : la paire ne correspond pas.
        $this->actingAs($technician)
            ->get(route('work-orders.attachments.show', [$mine, $attachment]))
            ->assertNotFound();
        $this->actingAs($technician)
            ->delete(route('work-orders.attachments.destroy', [$mine, $attachment]))
            ->assertNotFound();

        $this->assertNotSoftDeleted($attachment);
    }

    public function test_invoice_file_is_private_and_scoped_to_its_purchase_order(): void
    {
        $manager = User::factory()->manager()->create();
        Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create();
        $otherOrder = PurchaseOrder::factory()->create();

        $this->actingAs($manager)->post(route('purchase-orders.invoices.store', $order), [
            'invoice_number' => 'F-2026-001',
            'invoice_date' => '2026-10-01',
            'amount' => 150000,
            'file' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        Storage::disk('local')->assertExists($invoice->file_path);

        $this->actingAs($manager)->get($invoice->file_url)->assertOk();
        $this->actingAs($manager)->get(route('purchase-orders.invoices.file', [$otherOrder, $invoice]))->assertNotFound();
        $this->actingAs(User::factory()->technicien()->create())->get($invoice->file_url)->assertForbidden();
    }

    // ===== Retrait d'une pièce jointe =====

    public function test_removal_keeps_the_file_and_is_logged(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $attachment = $this->attach($workOrder, $technician);

        $this->actingAs($technician)
            ->delete(route('work-orders.attachments.destroy', [$workOrder, $attachment]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($attachment);
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->assertSame(0, $workOrder->attachments()->count());
        $this->assertTrue(ActivityLog::where('action', 'work_order.attachment_deleted')->where('user_id', $technician->id)->exists());
    }

    public function test_technician_cannot_remove_a_file_added_by_someone_else(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $attachment = $this->attach($workOrder, User::factory()->manager()->create());

        $this->actingAs($technician)
            ->delete(route('work-orders.attachments.destroy', [$workOrder, $attachment]))
            ->assertForbidden();

        $this->assertNotSoftDeleted($attachment);
    }

    public function test_supervisor_can_remove_any_file_of_the_work_order(): void
    {
        $technician = User::factory()->technicien()->create();
        $workOrder = $this->makeWorkOrder($technician);
        $attachment = $this->attach($workOrder, $technician);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('work-orders.attachments.destroy', [$workOrder, $attachment]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($attachment);
    }
}
