<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterventionReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_performed' => ['required', 'string', 'max:2000'],
            'parts_used' => ['nullable', 'string', 'max:1000'],
            'recommendations' => ['nullable', 'string', 'max:1000'],

            // La signature arrive en base64 depuis le canvas JavaScript (on verra ça à l'étape des vues)
            'signature' => ['nullable', 'string'],
            'signed_by_name' => ['nullable', 'string', 'max:255', 'required_with:signature'],
        ];
    }

    public function messages(): array
    {
        return [
            'work_performed.required' => 'Veuillez décrire le travail effectué.',
            'work_performed.max' => 'La description ne doit pas dépasser 2000 caractères.',
            'signed_by_name.required_with' => 'Le nom du signataire est obligatoire si une signature est fournie.',
        ];
    }
}