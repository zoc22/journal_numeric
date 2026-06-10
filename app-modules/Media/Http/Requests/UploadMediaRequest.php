<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête d'upload de média
 *
 * Valide les fichiers téléversés selon leur type.
 */
class UploadMediaRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        $type = $this->input('type', 'image');

        $allowedExtensions = config("media.allowed_types.{$type}.extensions", ['jpg', 'jpeg', 'png']);
        $maxSize = config("media.allowed_types.{$type}.max_size", 5 * 1024 * 1024);

        return [
            'file' => [
                'required',
                'file',
                'mimes:' . implode(',', $allowedExtensions),
                'max:' . ($maxSize / 1024),
            ],
            'type' => [
                'nullable',
                'string',
                Rule::in(['image', 'document', 'video', 'audio']),
            ],
            'is_public' => [
                'nullable',
                'boolean',
            ],
            'quality' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'allow_duplicate' => [
                'nullable',
                'boolean',
            ],
            'continent' => [
                'nullable',
                'string',
                'max:255',
            ],
            'pays' => [
                'nullable',
                'string',
                'max:255',
            ],
            'ville' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Aucun fichier n\'a été téléversé',
            'file.file' => 'Le fichier est invalide',
            'file.mimes' => 'Le type de fichier n\'est pas autorisé',
            'file.max' => 'Le fichier dépasse la taille maximale autorisée',
            'quality.min' => 'La qualité doit être comprise entre 1 et 100',
            'quality.max' => 'La qualité doit être comprise entre 1 et 100',
        ];
    }
}
