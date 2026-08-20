<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.required' => 'Le numéro de facture est obligatoire.',
            'amount.required' => 'Le montant est obligatoire.',
            'file.mimes' => 'Le fichier doit être un PDF, JPG ou PNG.',
        ];
    }
}