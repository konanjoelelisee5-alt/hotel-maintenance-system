<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseOrderReceptionController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $request->validate([
            'received' => ['required', 'array'],
            'received.*' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('received') as $itemId => $receivedQuantity) {
            $item = PurchaseOrderItem::findOrFail($itemId);

            $receivedQuantity = min($receivedQuantity, $item->quantity);
            $previouslyReceived = $item->received_quantity;
            $newlyReceived = $receivedQuantity - $previouslyReceived;

            $item->update(['received_quantity' => $receivedQuantity]);

            // Si cette ligne est liée à une pièce de stock, et qu'une quantité
            // supplémentaire vient d'être reçue, on alimente le stock automatiquement.
            if ($item->part_id && $newlyReceived > 0) {
                $item->part->recordMovement('entree', $newlyReceived, [
                    'purchase_order_id' => $purchaseOrder->id,
                    'note' => 'Réception commande ' . $purchaseOrder->number,
                ]);
            }
        }

        $purchaseOrder->refresh();
        $newStatus = $purchaseOrder->is_fully_received ? 'receptionnee' : 'reception_partielle';
        $purchaseOrder->update(['status' => $newStatus]);

        return back()->with('success', 'Réception enregistrée avec succès.');
    }
}