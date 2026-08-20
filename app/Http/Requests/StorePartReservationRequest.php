<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'part_id.required' => 'Veuillez sélectionner une pièce.',
            'quantity.min' => 'La quantité doit être d\'au moins 1.',
        ];
    }
}