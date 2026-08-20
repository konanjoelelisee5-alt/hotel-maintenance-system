<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skills' => ['nullable', 'array'],
            'skills.*' => ['exists:skills,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'skills.*.exists' => 'Une des compétences sélectionnées est invalide.',
        ];
    }
}