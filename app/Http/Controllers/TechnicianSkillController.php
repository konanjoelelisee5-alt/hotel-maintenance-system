<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTechnicianSkillsRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TechnicianSkillController extends Controller
{
    public function edit(User $technician): View
    {
        abort_if($technician->role !== 'technicien', 404);

        $skills = Skill::orderBy('name')->get();

        return view('planning.skills', compact('technician', 'skills'));
    }

    public function update(UpdateTechnicianSkillsRequest $request, User $technician): RedirectResponse
    {
        abort_if($technician->role !== 'technicien', 404);

        $technician->skills()->sync($request->validated('skills', []));

        return back()->with('success', 'Compétences mises à jour avec succès.');
    }
}