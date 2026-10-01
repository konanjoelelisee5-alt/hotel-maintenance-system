<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Avant la validation : un non-intervenant doit recevoir un 403, pas une
        // erreur de formulaire sur le statut.
        return $this->user()->can('intervene', $this->route('workOrder'));
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'in:ouvert,en_cours,en_attente,resolu,ferme',
                // Le technicien déclare "résolu" ; la fermeture revient au manager
                // (ou au contrôle qualité, qui ferme l'OT de lui-même).
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === 'ferme' && ! $this->user()->role->dispatchesWork()) {
                        $fail('Seul un manager peut fermer un ordre de travail. Passez-le en « Résolu ».');
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}