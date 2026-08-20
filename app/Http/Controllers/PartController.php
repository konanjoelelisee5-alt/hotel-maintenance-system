<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartRequest;
use App\Models\Part;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartController extends Controller
{
    public function index(Request $request): View
    {
        $parts = Part::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('sku', 'like', '%' . $request->search . '%'))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('quantity_on_hand', '<=', 'reorder_threshold'))
            ->orderBy('name')
            ->paginate(20);

        return view('parts.index', compact('parts'));
    }

    public function create(): View
    {
        return view('parts.create');
    }

    public function store(StorePartRequest $request): RedirectResponse
    {
        $part = Part::create($request->validated());

        return redirect()->route('parts.show', $part)->with('success', 'Pièce créée avec succès.');
    }

    public function show(Part $part): View
    {
        $part->load(['stockMovements.creator', 'stockMovements.workOrder', 'reservations.workOrder']);

        return view('parts.show', compact('part'));
    }

    public function edit(Part $part): View
    {
        return view('parts.edit', compact('part'));
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $part->update($validated);

        return redirect()->route('parts.show', $part)->with('success', 'Pièce mise à jour.');
    }

    public function destroy(Part $part): RedirectResponse
    {
        $part->update(['is_active' => false]);

        return back()->with('success', 'Pièce désactivée.');
    }
}