<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Part;
use Illuminate\Http\RedirectResponse;

class StockMovementController extends Controller
{
    public function store(StoreStockMovementRequest $request, Part $part): RedirectResponse
    {
        $part->recordMovement(
            $request->validated('type'),
            $request->validated('quantity'),
            ['note' => $request->validated('note')]
        );

        return back()->with('success', 'Mouvement de stock enregistré.');
    }
}