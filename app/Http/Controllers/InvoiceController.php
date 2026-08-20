<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function store(StoreInvoiceRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $filePath = null;

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('invoices/' . $purchaseOrder->id, 'public');
        }

        $purchaseOrder->invoices()->create([
            'invoice_number' => $request->validated('invoice_number'),
            'invoice_date' => $request->validated('invoice_date'),
            'amount' => $request->validated('amount'),
            'file_path' => $filePath,
        ]);

        $purchaseOrder->update(['status' => 'facturee']);

        return back()->with('success', 'Facture enregistrée avec succès.');
    }

    public function markAsPaid(PurchaseOrder $purchaseOrder, \App\Models\Invoice $invoice): RedirectResponse
    {
        $invoice->update([
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Facture marquée comme payée.');
    }
}