<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.required' => 'Veuillez sélectionner un technicien.',
            'assigned_to.exists' => 'Le technicien sélectionné est invalide.',
            'scheduled_at.required' => 'La date/heure de planification est obligatoire.',
            'estimated_duration_minutes.required' => 'La durée estimée est obligatoire.',
            'estimated_duration_minutes.min' => 'La durée minimale est de 15 minutes.',
            'estimated_duration_minutes.max' => 'La durée maximale est de 8 heures (480 minutes).',
        ];
    }
}