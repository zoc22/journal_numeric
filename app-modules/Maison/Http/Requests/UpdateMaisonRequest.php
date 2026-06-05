<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Maison\Models\Maison;

/**
 * Request pour la mise à jour d'une maison d'édition.
 *
 * @property string|null $nom Nom de la maison
 * @property string|null $description Description
 * @property string|null $logo_url URL du logo
 * @property string|null $email_contact Email de contact
 */
class UpdateMaisonRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * Seul un Super Admin ou l'Éditeur en Chef de la maison peut modifier.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Super Admin peut tout modifier
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Pour les membres de maison, on récupère la maison de l'utilisateur
        $membre = $user->membres()->where('est_actif', true)->first();
        if (!$membre) {
            return false;
        }

        $maison = $membre->maison;

        // Éditeur en Chef peut modifier sa propre maison
        if ($maison && $maison->aLeRole($user->id, 'editeur_en_chef')) {
            return true;
        }

        return false;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maisonId = $this->route('maison')?->id;

        return [
            'nom' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique(Maison::class, 'nom')->ignore($maisonId),
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
                'sometimes',
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
            'nom.unique' => 'Une maison d\'édition avec ce nom existe déjà.',
            'email_contact.email' => 'Veuillez fournir un email de contact valide.',
            'logo_url.url' => 'Veuillez fournir une URL valide pour le logo.',
        ];
    }
}
