<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderStatusRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    
    public function index(Request $request): View
    {
        // Onglets par étape du cycle d'achat ; « status » reste accepté (anciens liens).
        $groups = [
            'open' => ['brouillon', 'envoyee', 'confirmee', 'reception_partielle'],
            'received' => ['receptionnee'],
            'invoiced' => ['facturee'],
            'cancelled' => ['annulee'],
        ];
        $tab = array_key_exists((string) $request->tab, $groups) ? (string) $request->tab : 'all';
        $applyTab = fn ($q, string $key) => $key === 'all' ? $q : $q->whereIn('status', $groups[$key]);

        $purchaseOrders = $applyTab(PurchaseOrder::with(['supplier', 'workOrder']), $tab)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s
                ->where('number', 'like', '%'.$request->search.'%')
                ->orWhereHas('supplier', fn ($f) => $f->where('name', 'like', '%'.$request->search.'%'))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = collect(['all', ...array_keys($groups)])->mapWithKeys(fn ($key) => [$key => $applyTab(PurchaseOrder::query(), $key)->count()]);
        $stats = [
            'open' => $counts['open'],
            'committed' => PurchaseOrder::whereIn('status', $groups['open'])->sum('total_amount'),
            'toReceive' => PurchaseOrder::whereIn('status', ['envoyee', 'confirmee', 'reception_partielle'])->count(),
            'monthSpend' => PurchaseOrder::whereIn('status', ['receptionnee', 'facturee'])->where('order_date', '>=', now()->startOfMonth())->sum('total_amount'),
        ];

        return view('purchase-orders.index', compact('purchaseOrders', 'tab', 'counts', 'stats'));
    }

    public function create(Request $request): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $workOrders = WorkOrder::open()->orderBy('title')->get();

        // Pré-remplissage depuis la fiche d'un OT ou d'un fournisseur.
        $selectedWorkOrderId = $request->query('work_order_id');
        $selectedSupplierId = $request->query('supplier_id');
        // Pièces du catalogue, pour relier une ligne au stock (prix proposé = coût unitaire).
        $parts = \App\Models\Part::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'unit_cost', 'quantity_on_hand', 'reorder_threshold']);

        return view('purchase-orders.create', compact('suppliers', 'workOrders', 'selectedWorkOrderId', 'selectedSupplierId', 'parts'));
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        // On utilise une transaction : soit TOUT s'enregistre (commande + toutes ses lignes),
        // soit RIEN ne s'enregistre en cas d'erreur en cours de route.
        $purchaseOrder = DB::transaction(function () use ($request) {
            $purchaseOrder = PurchaseOrder::create([
                'number' => PurchaseOrder::generateNumber(),
                'supplier_id' => $request->validated('supplier_id'),
                'work_order_id' => $request->validated('work_order_id'),
                'created_by' => Auth::id(),
                'status' => 'brouillon',
                'order_date' => $request->validated('order_date'),
                'expected_delivery_date' => $request->validated('expected_delivery_date'),
                'notes' => $request->validated('notes'),
            ]);

            foreach ($request->validated('items') as $item) {
                $purchaseOrder->items()->create($item);
            }

            return $purchaseOrder;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Bon de commande ' . $purchaseOrder->number . ' créé avec succès.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'workOrder', 'creator', 'items', 'invoices']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function updateStatus(UpdatePurchaseOrderStatusRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->update(['status' => $request->validated('status')]);

        return back()->with('success', 'Statut mis à jour avec succès.');
    }
}