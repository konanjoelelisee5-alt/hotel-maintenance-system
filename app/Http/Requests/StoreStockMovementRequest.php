<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:entree,sortie,ajustement'],
            'quantity' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de mouvement est obligatoire.',
            'quantity.required' => 'La quantité est obligatoire.',
        ];
    }
}