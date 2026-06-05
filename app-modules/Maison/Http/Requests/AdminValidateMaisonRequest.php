<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour la validation d'une maison par l'admin plateforme.
 *
 * @property string|null $commentaire Commentaire optionnel sur la validation
 */
class AdminValidateMaisonRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * Seul un Admin Plateforme ou Super Admin peut valider une maison.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('admin_plateforme') || $user->hasRole('super_admin'));
    }

    /**
     * Règles de validation.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
