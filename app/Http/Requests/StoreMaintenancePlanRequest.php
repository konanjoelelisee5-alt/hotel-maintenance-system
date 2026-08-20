<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'equipment_id' => ['nullable', 'exists:equipment,id'],
            'work_order_type_id' => ['required', 'exists:work_order_types,id'],
            'work_order_priority_id' => ['required', 'exists:work_order_priorities,id'],
            'checklist_template_id' => ['nullable', 'exists:checklist_templates,id'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'skill_id' => ['nullable', 'exists:skills,id'],
            'frequency_unit' => ['required', 'in:jour,semaine,mois,trimestre,annee'],
            'frequency_interval' => ['required', 'integer', 'min:1'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du plan est obligatoire.',
            'work_order_type_id.required' => "Le type d'OT à générer est obligatoire.",
            'work_order_priority_id.required' => "La priorité de l'OT à générer est obligatoire.",
            'frequency_unit.required' => 'La fréquence est obligatoire.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('room_id') && ! $this->filled('equipment_id')) {
                $validator->errors()->add('room_id', 'Le plan doit cibler au moins une chambre ou un équipement.');
            }
        });
    }
}
