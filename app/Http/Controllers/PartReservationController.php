<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartReservationRequest;
use App\Models\PartReservation;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PartReservationController extends Controller
{
    public function store(StorePartReservationRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);

        PartReservation::create([
            'part_id' => $request->validated('part_id'),
            'work_order_id' => $workOrder->id,
            'quantity' => $request->validated('quantity'),
            'reserved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pièce réservée avec succès.');
    }

    public function withdraw(WorkOrder $workOrder, PartReservation $reservation): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        abort_if($reservation->work_order_id !== $workOrder->id, 404);

        $reservation->markAsWithdrawn();

        return back()->with('success', 'Sortie de stock confirmée.');
    }

    public function cancel(WorkOrder $workOrder, PartReservation $reservation): RedirectResponse
    {
        $this->authorize('intervene', $workOrder);
        abort_if($reservation->work_order_id !== $workOrder->id, 404);

        $reservation->cancel();

        return back()->with('success', 'Réservation annulée.');
    }
}