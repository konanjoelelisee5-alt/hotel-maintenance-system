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
        // Onglets : pièces actives, sous le seuil de réapprovisionnement, retirées du catalogue.
        // (« low_stock=1 » : ancien lien, gardé pour les favoris.)
        $tab = $request->boolean('low_stock') ? 'low' : (in_array($request->tab, ['low', 'inactive'], true) ? $request->tab : 'all');
        $applyTab = fn ($q, string $key) => match ($key) {
            'low' => $q->where('is_active', true)->whereColumn('quantity_on_hand', '<=', 'reorder_threshold'),
            'inactive' => $q->where('is_active', false),
            default => $q->where('is_active', true),
        };

        $parts = $applyTab(Part::query(), $tab)
            // Recherche groupée : sans parenthèses, le « OU » annulait le filtre d'onglet.
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('sku', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $active = Part::where('is_active', true);
        $stats = [
            'references' => (clone $active)->count(),
            'low' => $applyTab(Part::query(), 'low')->count(),
            'value' => (clone $active)->sum(\Illuminate\Support\Facades\DB::raw('quantity_on_hand * unit_cost')),
            'reserved' => (clone $active)->sum('quantity_reserved'),
        ];
        $counts = collect(['all', 'low', 'inactive'])->mapWithKeys(fn ($key) => [$key => $applyTab(Part::query(), $key)->count()]);

        return view('parts.index', compact('parts', 'tab', 'stats', 'counts'));
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