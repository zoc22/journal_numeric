<?php
// app-modules/User/Http/Requests/CreateUserRequest.php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de création d'utilisateur.
 *
 * Cette classe contient toutes les règles de validation
 * pour la création d'un nouvel utilisateur.
 */
class CreateUserRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     *
     * La permission est gérée par le middleware au niveau de la route
     * (permission:user.manage), donc cette méthode ne fait que retourner true.
     *
     * Les vérifications d'autorisation se font via les middlewares de la route,
     * pas dans la FormRequest.
     *
     * @return bool True si l'utilisateur est autorisé (toujours true ici)
     */
    public function authorize(): bool
    {
        // L'autorisation est gérée par le middleware 'permission:user.manage' dans la route
        // Cette méthode retourne simplement true
        return true;
    }

    /**
     * Règles de validation pour la création d'un utilisateur.
     *
     * Les règles définissent les critères que les données doivent respecter :
     * - nom : obligatoire, chaîne, max 255 caractères
     * - prenom : optionnel, chaîne, max 255 caractères
     * - email : obligatoire, format email valide, unique dans la table users
     * - password : obligatoire, min 8 caractères, doit être confirmé (password_confirmation)
     * - telephone : optionnel, chaîne, max 20 caractères
     * - poste : optionnel, chaîne, max 255 caractères
     * - role : optionnel, doit exister dans la table roles
     * - is_active : optionnel, doit être un booléen
     *
     * @return array<string, mixed> Les règles de validation
     */
    public function rules(): array
    {
        return [
            // Nom obligatoire
            'nom' => 'required|string|max:255',
            // Prénom optionnel
            'prenom' => 'nullable|string|max:255',
            // Email obligatoire et unique
            'email' => 'required|email|unique:users,email',
            // Password obligatoire avec confirmation
            'password' => 'required|string|min:8|confirmed',
            // Téléphone optionnel
            'telephone' => 'nullable|string|max:20',
            // Continent, Pays, Ville obligatoires
            'continent' => 'required|string|max:255',
            'pays' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            // Poste optionnel
            'poste' => 'nullable|string|max:255',
            // Rôle optionnel (doit exister dans la table roles)
            'role' => 'nullable|string|exists:roles,name',
            // Statut actif optionnel (booléen)
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Messages d'erreur personnalisés (en français).
     *
     * Ces messages sont affichés à l'utilisateur si la validation échoue.
     * Chaque clé correspond à une règle et chaque message est en français
     * pour une meilleure expérience utilisateur.
     *
     * @return array<string, string> Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'Veuillez fournir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'continent.required' => 'Le continent est obligatoire.',
            'pays.required' => 'Le pays est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'role.exists' => 'Le rôle sélectionné n\'existe pas.',
        ];
    }
}
