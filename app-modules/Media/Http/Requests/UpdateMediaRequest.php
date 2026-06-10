<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de mise à jour de média
 */
class UpdateMediaRequest extends FormRequest
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
        return [
            'nom_original' => [
                'nullable',
                'string',
                'max:255',
            ],
            'est_publique' => [
                'nullable',
                'boolean',
            ],
            'metadonnees' => [
                'nullable',
                'array',
            ],
            'metadonnees.legende' => [
                'nullable',
                'string',
                'max:500',
            ],
            'metadonnees.credits' => [
                'nullable',
                'string',
                'max:255',
            ],
            'metadonnees.alt' => [
                'nullable',
                'string',
                'max:255',
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
}
