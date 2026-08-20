<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewQualityControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.is_compliant' => ['nullable', 'boolean'],
            'items.*.comment' => ['nullable', 'string', 'max:500'],

            'decision' => ['required', 'in:approuve,rejete'],
            'overall_comment' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejete'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Veuillez choisir Approuver ou Rejeter.',
            'overall_comment.required_if' => 'Un commentaire est obligatoire en cas de rejet, pour expliquer au technicien ce qu\'il faut corriger.',
        ];
    }
}