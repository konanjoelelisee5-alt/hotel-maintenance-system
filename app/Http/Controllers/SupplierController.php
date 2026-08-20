<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->orderBy('name')
            ->paginate(15);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Fournisseur créé avec succès.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['contracts', 'purchaseOrders' => fn ($q) => $q->latest()->take(10)]);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Fournisseur mis à jour avec succès.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->update(['is_active' => false]);

        return redirect()->route('suppliers.index')
            ->with('success', 'Fournisseur désactivé avec succès.');
    }
}