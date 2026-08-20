<?php

namespace App\Http\Controllers;

use App\Models\WorkOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderTypeController extends Controller
{
    public function index(): View
    {
        $types = WorkOrderType::orderBy('position')->get();

        return view('work-order-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('work-order-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:work_order_types,code'],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        WorkOrderType::create($validated);

        return redirect()->route('work-order-types.index')->with('success', 'Type d\'OT créé avec succès.');
    }

    public function edit(WorkOrderType $workOrderType): View
    {
        return view('work-order-types.edit', compact('workOrderType'));
    }

    public function update(Request $request, WorkOrderType $workOrderType): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $workOrderType->update($validated);

        return redirect()->route('work-order-types.index')->with('success', 'Type d\'OT mis à jour.');
    }

    public function destroy(WorkOrderType $workOrderType): RedirectResponse
    {
        $workOrderType->update(['is_active' => false]);

        return back()->with('success', 'Type d\'OT désactivé.');
    }
}