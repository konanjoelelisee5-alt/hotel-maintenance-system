<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'type_id' => ['required', 'exists:work_order_types,id'],
            'priority_id' => ['required', 'exists:work_order_priorities,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'type_id.required' => 'Le type d\'OT est obligatoire.',
            'priority_id.required' => 'La priorité est obligatoire.',
        ];
    }
}