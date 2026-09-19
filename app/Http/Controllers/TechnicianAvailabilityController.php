<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreTechnicianAvailabilityRequest;
use App\Models\TechnicianAvailability;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TechnicianAvailabilityController extends Controller
{
    public function index(User $technician): View
    {
        abort_if($technician->role !== UserRole::Technicien, 404);

        $availabilities = $technician->availabilities()->orderBy('day_of_week')->get();

        return view('planning.availabilities', compact('technician', 'availabilities'));
    }

    public function store(StoreTechnicianAvailabilityRequest $request, User $technician): RedirectResponse
    {
        abort_if($technician->role !== UserRole::Technicien, 404);

        $technician->availabilities()->create($request->validated());

        return back()->with('success', 'Disponibilité ajoutée avec succès.');
    }

    public function destroy(User $technician, TechnicianAvailability $availability): RedirectResponse
    {
        abort_if($availability->user_id !== $technician->id, 404);

        $availability->delete();

        return back()->with('success', 'Disponibilité supprimée avec succès.');
    }
}