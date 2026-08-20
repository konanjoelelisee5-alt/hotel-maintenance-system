<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:disponible,conge,absence'],

            // Requis uniquement si type = disponible (créneau récurrent)
            'day_of_week' => ['required_if:type,disponible', 'nullable', 'integer', 'between:0,6'],
            'start_time' => ['required_if:type,disponible', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:type,disponible', 'nullable', 'date_format:H:i', 'after:start_time'],

            // Requis uniquement si type = congé/absence (période ponctuelle)
            'date_start' => ['required_if:type,conge,absence', 'nullable', 'date'],
            'date_end' => ['required_if:type,conge,absence', 'nullable', 'date', 'after_or_equal:date_start'],

            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type est obligatoire.',
            'day_of_week.required_if' => 'Le jour de la semaine est obligatoire pour une disponibilité récurrente.',
            'start_time.required_if' => 'L\'heure de début est obligatoire.',
            'end_time.required_if' => 'L\'heure de fin est obligatoire.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'date_start.required_if' => 'La date de début est obligatoire pour un congé/absence.',
            'date_end.required_if' => 'La date de fin est obligatoire.',
            'date_end.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
        ];
    }
}