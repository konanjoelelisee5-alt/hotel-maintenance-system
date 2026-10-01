<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Avancement déclaré par l'intervenant assigné : en cours, en attente, résolu.
 * Les superviseurs (admin, manager) pilotent par leurs propres actions
 * (suspendre, relancer, annuler : WorkOrderPilotController) et la fermeture
 * passe uniquement par le contrôle qualité.
 */
class UpdateWorkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Avant la validation : un non-intervenant doit recevoir un 403, pas une
        // erreur de formulaire sur le statut.
        return $this->user()->can('perform', $this->route('workOrder'));
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'in:en_cours,en_attente,resolu,ferme',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === 'ferme') {
                        $fail('La fermeture se fait par le contrôle qualité. Passez l\'OT en « Résolu ».');
                    }
                },
            ],
            // Mettre en attente sans dire pourquoi bloque le manager : motif obligatoire.
            'note' => ['nullable', 'required_if:status,en_attente', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné est invalide.',
            'note.required_if' => 'Indiquez pourquoi l\'OT est en attente (pièce, accès à la chambre…).',
        ];
    }
}
