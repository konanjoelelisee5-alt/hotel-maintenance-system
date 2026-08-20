<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Validation du tableau de lignes
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Veuillez sélectionner un fournisseur.',
            'items.required' => 'Veuillez ajouter au moins un article.',
            'items.min' => 'Veuillez ajouter au moins un article.',
            'items.*.description.required' => 'La description de chaque article est obligatoire.',
            'items.*.quantity.min' => 'La quantité doit être d\'au moins 1.',
            'items.*.unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
        ];
    }
}