<?php

namespace App\Http\Requests;

/**
 * Précision ajoutée à un signalement Housekeeping (texte, message vocal, photo) :
 * au moins l'un des trois. Même réponse JSON que le signalement (envoi en fetch).
 */
class ComplementQuickReportRequest extends StoreQuickReportRequest
{
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'required_without_all:audio,photo', 'string', 'max:1000'],
            'audio' => ['nullable', 'file', 'max:10240', 'mimetypes:'.implode(',', self::AUDIO_MIMETYPES)],
            'photo' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required_without_all' => 'Ajoutez un message vocal, une photo ou quelques mots.',
            'audio.max' => 'Le message vocal est trop long.',
            'audio.mimetypes' => 'Le message vocal n\'a pas pu être lu. Réessayez.',
            'photo.image' => 'La photo n\'a pas pu être lue. Réessayez.',
            'photo.max' => 'La photo est trop lourde.',
        ];
    }
}
