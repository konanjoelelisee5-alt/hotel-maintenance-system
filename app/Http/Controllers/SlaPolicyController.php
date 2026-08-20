<?php

namespace App\Http\Controllers;

use App\Models\SlaPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function index(): View
    {
        $policies = SlaPolicy::orderBy('priority')->orderBy('work_order_type')->get();

        return view('sla-policies.index', compact('policies'));
    }

    public function create(): View
    {
        return view('sla-policies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'in:basse,moyenne,haute,urgente'],
            'work_order_type' => ['nullable', 'in:maintenance,demande_client,preventif'],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
        ]);

        SlaPolicy::create($validated);

        return redirect()->route('sla-policies.index')->with('success', 'Politique SLA créée avec succès.');
    }

    public function edit(SlaPolicy $slaPolicy): View
    {
        return view('sla-policies.edit', compact('slaPolicy'));
    }

    public function update(Request $request, SlaPolicy $slaPolicy): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'in:basse,moyenne,haute,urgente'],
            'work_order_type' => ['nullable', 'in:maintenance,demande_client,preventif'],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $slaPolicy->update($validated);

        return redirect()->route('sla-policies.index')->with('success', 'Politique SLA mise à jour.');
    }

    public function destroy(SlaPolicy $slaPolicy): RedirectResponse
    {
        $slaPolicy->update(['is_active' => false]);

        return back()->with('success', 'Politique SLA désactivée.');
    }
}