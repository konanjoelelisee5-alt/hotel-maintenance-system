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
        // Onglets : fournisseurs actifs / désactivés ; chaque ligne montre son volume d'achats.
        $tab = $request->tab === 'inactive' ? 'inactive' : 'active';
        $openStatuses = ['brouillon', 'envoyee', 'confirmee', 'reception_partielle'];

        $suppliers = Supplier::where('is_active', $tab === 'active')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('contact_person', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->withCount(['purchaseOrders', 'purchaseOrders as open_orders_count' => fn ($q) => $q->whereIn('status', $openStatuses)])
            ->withSum(['purchaseOrders as purchased_amount' => fn ($q) => $q->where('status', '!=', 'annulee')], 'total_amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $counts = ['active' => Supplier::where('is_active', true)->count(), 'inactive' => Supplier::where('is_active', false)->count()];
        $stats = [
            'active' => $counts['active'],
            'openOrders' => \App\Models\PurchaseOrder::whereIn('status', $openStatuses)->count(),
            'yearSpend' => \App\Models\PurchaseOrder::where('status', '!=', 'annulee')->whereYear('order_date', now()->year)->sum('total_amount'),
        ];

        return view('suppliers.index', compact('suppliers', 'tab', 'counts', 'stats'));
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
        $supplier->load(['purchaseOrders' => fn ($q) => $q->with('workOrder')->latest()->take(10)]);

        $orders = $supplier->purchaseOrders();
        $stats = [
            'count' => (clone $orders)->count(),
            'open' => (clone $orders)->whereIn('status', ['brouillon', 'envoyee', 'confirmee', 'reception_partielle'])->count(),
            'total' => (clone $orders)->where('status', '!=', 'annulee')->sum('total_amount'),
            'last' => (clone $orders)->max('order_date'),
        ];

        return view('suppliers.show', compact('supplier', 'stats'));
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