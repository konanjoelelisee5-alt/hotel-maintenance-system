<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'status' => ['required', Rule::in(array_keys(Equipment::STATUS_LABELS))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom de l'équipement est obligatoire.",
            'room_id.exists' => 'Le lieu sélectionné est invalide.',
        ];
    }
}
