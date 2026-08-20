<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Veuillez sélectionner au moins un fichier.',
            'files.*.max' => 'Chaque fichier ne doit pas dépasser 10 Mo.',
            'files.*.mimes' => 'Formats autorisés : jpg, jpeg, png, pdf, doc, docx.',
        ];
    }
}