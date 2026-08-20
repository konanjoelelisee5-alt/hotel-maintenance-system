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
        $purchaseOrders = PurchaseOrder::with(['supplier', 'workOrder'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(Request $request): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $workOrders = WorkOrder::open()->orderBy('title')->get();

        // Permet de pré-remplir le work_order_id si on arrive depuis la fiche d'un OT
        $selectedWorkOrderId = $request->query('work_order_id');

        return view('purchase-orders.create', compact('suppliers', 'workOrders', 'selectedWorkOrderId'));
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