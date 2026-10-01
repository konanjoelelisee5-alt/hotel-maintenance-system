<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Changer d'e-mail redirige « mot de passe oublié » : une session restée
            // ouverte ne doit pas suffire, on redemande le mot de passe actuel.
            'current_password' => [
                Rule::requiredIf(fn () => $this->input('email') !== $this->user()->email),
                'nullable',
                'current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Saisissez votre mot de passe actuel pour changer d\'adresse e-mail.',
            'current_password.current_password' => 'Mot de passe incorrect.',
        ];
    }
}
