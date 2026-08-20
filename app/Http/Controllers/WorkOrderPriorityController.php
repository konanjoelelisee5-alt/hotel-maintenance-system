<?php

namespace App\Http\Controllers;

use App\Models\WorkOrderPriority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderPriorityController extends Controller
{
    public function index(): View
    {
        $priorities = WorkOrderPriority::orderBy('position')->get();

        return view('work-order-priorities.index', compact('priorities'));
    }

    public function create(): View
    {
        return view('work-order-priorities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:work_order_priorities,code'],
            'label' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        WorkOrderPriority::create($validated);

        return redirect()->route('work-order-priorities.index')->with('success', 'Priorité créée avec succès.');
    }

    public function edit(WorkOrderPriority $workOrderPriority): View
    {
        return view('work-order-priorities.edit', compact('workOrderPriority'));
    }

    public function update(Request $request, WorkOrderPriority $workOrderPriority): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:7'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $workOrderPriority->update($validated);

        return redirect()->route('work-order-priorities.index')->with('success', 'Priorité mise à jour.');
    }

    public function destroy(WorkOrderPriority $workOrderPriority): RedirectResponse
    {
        $workOrderPriority->update(['is_active' => false]);

        return back()->with('success', 'Priorité désactivée.');
    }
}