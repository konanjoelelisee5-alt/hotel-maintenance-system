<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', 'unique:parts,sku'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'La référence (SKU) est obligatoire.',
            'sku.unique' => 'Cette référence existe déjà.',
            'name.required' => 'Le nom de la pièce est obligatoire.',
        ];
    }
}