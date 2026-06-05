<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Maison\Models\Maison;

/**
 * Request pour la création d'une nouvelle maison d'édition.
 *
 * @property string $nom Nom de la maison
 * @property string $description Description
 * @property string $logo_url URL du logo
 * @property string $email_contact Email de contact
 */
class CreateMaisonRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * Le Super Admin et l'Admin Plateforme peuvent créer une maison.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin_plateforme'));
    }

    /**
     * Règles de validation.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nom' => [
                'required',
                'string',
                'max:255',
                'unique:' . Maison::class . ',nom',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'logo_url' => [
                'nullable',
                'string',
                'max:500',
                'url',
            ],
            'email_contact' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la maison d\'édition est obligatoire.',
            'nom.unique' => 'Une maison d\'édition avec ce nom existe déjà.',
            'email_contact.required' => 'L\'email de contact est obligatoire.',
            'email_contact.email' => 'Veuillez fournir un email de contact valide.',
            'logo_url.url' => 'Veuillez fournir une URL valide pour le logo.',
        ];
    }
}
