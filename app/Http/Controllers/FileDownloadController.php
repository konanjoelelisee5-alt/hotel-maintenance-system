<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Les fichiers (photos, messages vocaux, factures) sont sur le disque privé :
 * ils ne sont lisibles qu'à travers ces routes, après contrôle des droits.
 * Avant, un simple lien /storage/... suffisait, même sans être connecté.
 */
class FileDownloadController extends Controller
{
    public const DISK = 'local';

    public function workOrderAttachment(WorkOrder $workOrder, WorkOrderAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $workOrder);

        return $this->stream($attachment->file_path, $attachment->original_name);
    }

    /** Route déjà réservée admin/manager : la facture doit appartenir au bon de commande. */
    public function invoice(PurchaseOrder $purchaseOrder, Invoice $invoice): StreamedResponse
    {
        abort_unless($invoice->file_path, 404);

        return $this->stream($invoice->file_path, "facture-{$invoice->invoice_number}.".pathinfo($invoice->file_path, PATHINFO_EXTENSION));
    }

    private function stream(string $path, string $name): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, $name, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
