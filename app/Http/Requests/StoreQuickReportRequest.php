<?php

namespace App\Http\Requests;

use App\Enums\IssueCategory;
use App\Models\Room;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreQuickReportRequest extends FormRequest
{
    /** Durée maximale d'un message vocal, en secondes (appliquée par l'enregistreur). */
    public const MAX_AUDIO_SECONDS = 120;

    /**
     * Formats produits par l'enregistreur des navigateurs : webm/opus (Chrome,
     * Android, Firefox), mp4/aac (Safari, iPhone). Un webm ou mp4 sans image est
     * souvent détecté comme "video/*" côté serveur, d'où leur présence ici.
     */
    private const AUDIO_MIMETYPES = [
        'audio/webm', 'video/webm', 'audio/ogg', 'audio/mp4', 'video/mp4',
        'audio/x-m4a', 'audio/aac', 'audio/mpeg', 'audio/wav', 'audio/x-wav',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(IssueCategory::class)],
            'common_area' => ['boolean'],
            // Une chambre en service (un code d'espace commun tapé au clavier ne compte pas).
            'room_number' => ['nullable', 'required_unless:common_area,1', 'string', Rule::exists('rooms', 'number')
                ->where('type', Room::TYPE_ROOM)->whereNot('status', 'hors_service')],
            'common_area_id' => ['nullable', Rule::exists('rooms', 'id')
                ->where('type', Room::TYPE_COMMON_AREA)->whereNot('status', 'hors_service')],
            'urgent' => ['boolean'],
            'audio' => ['nullable', 'file', 'max:10240', 'mimetypes:'.implode(',', self::AUDIO_MIMETYPES)],
            'photo' => ['nullable', 'image', 'max:10240'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * L'écran envoie en fetch et affiche lui-même le message : on répond en JSON
     * (bootstrap/app.php ne le fait que pour api/*, ce serait sinon une redirection).
     */
    protected function failedValidation(Validator $validator): void
    {
        if (! $this->expectsJson()) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Touchez une image pour dire quel est le problème.',
            'category.enum' => 'Touchez une image pour dire quel est le problème.',
            'room_number.required_unless' => 'Indiquez le numéro de la chambre.',
            'room_number.exists' => 'Ce numéro de chambre n\'existe pas.',
            'common_area_id.exists' => 'Ce lieu n\'est plus disponible. Choisissez « Autre endroit ».',
            'audio.max' => 'Le message vocal est trop long.',
            'audio.mimetypes' => 'Le message vocal n\'a pas pu être lu. Réessayez.',
            'photo.image' => 'La photo n\'a pas pu être lue. Réessayez.',
            'photo.max' => 'La photo est trop lourde.',
        ];
    }
}
