<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkillController extends Controller
{
    public function index(): View
    {
        $skills = Skill::withCount('users')->orderBy('name')->get();

        return view('skills.index', compact('skills'));
    }

    public function create(): View
    {
        return view('skills.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skills,name'],
        ]);

        Skill::create($validated);

        return redirect()->route('skills.index')->with('success', 'Compétence créée avec succès.');
    }

    public function edit(Skill $skill): View
    {
        return view('skills.edit', compact('skill'));
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skills,name,' . $skill->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $skill->update($validated);

        return redirect()->route('skills.index')->with('success', 'Compétence mise à jour.');
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->update(['is_active' => false]);

        return back()->with('success', 'Compétence désactivée.');
    }
}