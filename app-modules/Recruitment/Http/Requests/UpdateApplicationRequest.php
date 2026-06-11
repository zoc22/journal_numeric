<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de mise à jour d'une candidature
 */
class UpdateApplicationRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'lettre_motivation' => ['sometimes', 'string', 'min:50'],
            'experiences' => ['nullable', 'array'],
            'formations' => ['nullable', 'array'],
            'portfolio' => ['nullable', 'array'],
            'competences' => ['nullable', 'array'],
            'documents' => ['nullable', 'array'],
            'cv_url' => ['nullable', 'url'],
        ];
    }
}
