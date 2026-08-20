<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le commentaire ne peut pas être vide.',
            'content.max' => 'Le commentaire ne peut pas dépasser 2000 caractères.',
        ];
    }
}