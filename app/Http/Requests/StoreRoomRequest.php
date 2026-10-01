<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Codes d'espace commun homogènes : "pisc " et "PISC" sont le même lieu.
        if ($this->filled('number')) {
            $this->merge(['number' => mb_strtoupper(trim($this->input('number')))]);
        }
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', Rule::in([Room::TYPE_ROOM, Room::TYPE_COMMON_AREA])],
            'number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'number')->ignore($this->route('room'))],
            'name' => [Rule::requiredIf($type === Room::TYPE_COMMON_AREA), 'nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(Room::STATUSES[$type] ?? [])],
        ];
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Le numéro (ou code) est obligatoire.',
            'number.unique' => 'Ce numéro ou ce code est déjà utilisé par un autre lieu.',
            'name.required' => "Le nom de l'espace commun est obligatoire (ex. Piscine).",
            'status.in' => "Cet état n'est pas possible pour ce type de lieu.",
        ];
    }

    public function attributes(): array
    {
        return ['number' => 'numéro', 'name' => 'nom', 'floor' => 'étage', 'status' => 'état'];
    }
}
