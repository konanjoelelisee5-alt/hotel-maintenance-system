<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\RedirectResponse;

class CorrectionRequestController extends Controller
{
    /**
     * Marque une demande de correction comme traitée (typiquement après
     * que le technicien ait refait son intervention et resoumis l'OT).
     */
    public function markAsResolved(CorrectionRequest $correctionRequest): RedirectResponse
    {
        $correctionRequest->update([
            'status' => 'traitee',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Demande de correction marquée comme traitée.');
    }
}