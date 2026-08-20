<?php

namespace App\Http\Controllers;

use App\Models\EscalationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EscalationRuleController extends Controller
{
    public function index(): View
    {
        $rules = EscalationRule::orderBy('trigger_type')->get();

        return view('escalation-rules.index', compact('rules'));
    }

    public function create(): View
    {
        return view('escalation-rules.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:reponse_proche,reponse_depassee,resolution_proche,resolution_depassee'],
            'offset_minutes' => ['required', 'integer'],
            'notify_target' => ['required', 'in:technicien_assigne,manager,admin'],
        ]);

        EscalationRule::create($validated);

        return redirect()->route('escalation-rules.index')->with('success', 'Règle d\'escalade créée avec succès.');
    }

    public function edit(EscalationRule $escalationRule): View
    {
        return view('escalation-rules.edit', compact('escalationRule'));
    }

    public function update(Request $request, EscalationRule $escalationRule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:reponse_proche,reponse_depassee,resolution_proche,resolution_depassee'],
            'offset_minutes' => ['required', 'integer'],
            'notify_target' => ['required', 'in:technicien_assigne,manager,admin'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $escalationRule->update($validated);

        return redirect()->route('escalation-rules.index')->with('success', 'Règle mise à jour.');
    }

    public function destroy(EscalationRule $escalationRule): RedirectResponse
    {
        $escalationRule->update(['is_active' => false]);

        return back()->with('success', 'Règle désactivée.');
    }
}