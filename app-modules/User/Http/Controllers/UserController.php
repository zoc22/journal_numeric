<?php
// app-modules/User/Http/Controllers/UserController.php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Modules\User\Http\Requests\CreateUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;
use Modules\User\Http\Requests\AssignRoleRequest;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Services\UserService;
use Spatie\Permission\Models\Role;

/**
 * Contrôleur User – Gère toutes les opérations sur les utilisateurs.
 *
 * Ce contrôleur permet de :
 * - Lister les utilisateurs
 * - Créer un utilisateur
 * - Voir un utilisateur
 * - Modifier un utilisateur
 * - Supprimer un utilisateur
 * - Activer/désactiver un utilisateur
 * - Assigner/retirer des rôles
 * - Obtenir le profil de l'utilisateur connecté
 * - Modifier son propre profil
 *
 * Toutes les routes protégées nécessitent une permission spécifique.
 */
class UserController extends Controller
{
    /**
     * Le service qui contient la logique métier des utilisateurs.
     * L'injection de dépendance permet de découpler le contrôleur de la logique.
     *
     * @var UserService
     */
    protected UserService $userService;

    /**
     * Constructeur – injecte le service UserService.
     *
     * L'injection de dépendance permet à Laravel de gérer automatiquement
     * l'instanciation du service et ses dépendances.
     *
     * @param UserService $userService Le service métier pour les opérations utilisateurs
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Affiche la liste de tous les utilisateurs (avec pagination).
     *
     * Permission requise : user.manage
     *
     * GET /api/users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Nombre d'éléments par page (par défaut 15)
        $perPage = $request->input('per_page', 15);

        // Récupère les utilisateurs paginés
        $users = $this->userService->getPaginated($perPage);

        // Retourne la réponse avec la ressource formatée
        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * Permission requise : user.manage
     *
     * POST /api/users
     *
     * Validation : Effectuée par CreateUserRequest (email unique, password confirmée, etc.)
     * Sécurité : Le mot de passe est hashé avec bcrypt avant la sauvegarde.
     *
     * @param CreateUserRequest $request Requête validée contenant les données de l'utilisateur
     * @return JsonResponse JSON avec l'utilisateur créé (code 201)
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        // Récupère les données validées
        $validated = $request->validated();

        // Hashage du mot de passe pour la sécurité (bcrypt par défaut)
        $validated['password'] = Hash::make($validated['password']);

        // Création de l'utilisateur via le service métier
        $user = $this->userService->create($validated);

        // Assignation d'un rôle si fourni dans la requête
        if ($request->has('role')) {
            $user->assignRole($request->input('role'));
        }

        // Retourne l'utilisateur créé avec le code HTTP 201 (Created)
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Affiche les détails d'un utilisateur spécifique.
     *
     * Permission requise : user.manage
     *
     * GET /api/users/{user}
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Met à jour un utilisateur existant.
     *
     * Permission requise : user.manage
     *
     * PUT/PATCH /api/users/{user}
     *
     * Validation : Effectuée par UpdateUserRequest (email unique sauf pour cet user, password confirmée, etc.)
     * Sécurité : Le mot de passe est hashé avant la mise à jour s'il est modifié.
     *
     * @param UpdateUserRequest $request Requête validée avec les données à mettre à jour
     * @param User $user L'utilisateur à modifier (injecté via route model binding)
     * @return JsonResponse JSON avec l'utilisateur mis à jour
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        // Récupère les données validées
        $validated = $request->validated();

        // Hashage du mot de passe si un nouveau mot de passe est fourni
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Mise à jour de l'utilisateur via le service métier
        $this->userService->update($user, $validated);

        // Retourne l'utilisateur mis à jour avec les données rafraîchies
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Supprime un utilisateur (soft delete – suppression douce).
     *
     * Permission requise : user.manage
     *
     * DELETE /api/users/{user}
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    /**
     * Active un utilisateur (le rend de nouveau actif).
     *
     * Permission requise : user.manage
     *
     * POST /api/users/{user}/activate
     *
     * @param User $user
     * @return JsonResponse
     */
    public function activate(User $user): JsonResponse
    {
        $this->userService->activate($user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur activé avec succès',
        ]);
    }

    /**
     * Désactive un utilisateur (l'empêche de se connecter).
     *
     * Permission requise : user.manage
     *
     * POST /api/users/{user}/deactivate
     *
     * @param User $user
     * @return JsonResponse
     */
    public function deactivate(User $user): JsonResponse
    {
        $this->userService->deactivate($user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur désactivé avec succès',
        ]);
    }

    /**
     * Assigne un rôle à un utilisateur.
     *
     * Permission requise : user.role.assign
     *
     * POST /api/users/{user}/assign-role
     *
     * Corps de la requête :
     * {
     *   "role": "editeur_chef"
     * }
     *
     * Note : Un utilisateur peut avoir plusieurs rôles. Cette méthode en ajoute un supplémentaire.
     * Pour remplacer tous les rôles, utiliser syncRoles (non implémenté ici).
     *
     * @param AssignRoleRequest $request Requête validée contenant le nom du rôle
     * @param User $user L'utilisateur auquel assigner le rôle
     * @return JsonResponse JSON de confirmation
     */
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        // Récupère le nom du rôle depuis la requête
        $roleName = $request->input('role');

        // Cherche le rôle dans la base de données
        // La méthode findByName() lance une exception si le rôle n'existe pas
        $role = Role::findByName($roleName, 'sanctum');

        // Assigne le rôle à l'utilisateur via le service métier
        $this->userService->assignRole($user, $role);

        // Retourne une confirmation avec le rôle assigné
        return response()->json([
            'success' => true,
            'message' => "Rôle '{$roleName}' assigné avec succès",
        ]);
    }

    /**
     * Retire un rôle à un utilisateur.
     *
     * Permission requise : user.role.assign
     *
     * POST /api/users/{user}/remove-role
     *
     * Corps de la requête :
     * {
     *   "role": "editeur_chef"
     * }
     *
     * Note : Si l'utilisateur n'a pas ce rôle, la méthode s'exécute sans erreur (comportement sûr).
     *
     * @param AssignRoleRequest $request Requête validée contenant le nom du rôle
     * @param User $user L'utilisateur dont retirer le rôle
     * @return JsonResponse JSON de confirmation
     */
    public function removeRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        // Récupère le nom du rôle depuis la requête
        $roleName = $request->input('role');

        // Cherche le rôle dans la base de données
        $role = Role::findByName($roleName, 'sanctum');

        // Retire le rôle de l'utilisateur via le service métier
        $this->userService->removeRole($user, $role);

        // Retourne une confirmation avec le rôle retiré
        return response()->json([
            'success' => true,
            'message' => "Rôle '{$roleName}' retiré avec succès",
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     *
     * GET /api/me
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Met à jour le profil de l'utilisateur connecté.
     *
     * PUT /api/profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validation des données (règles simplifiées pour le profil)
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'continent' => 'sometimes|string|max:255',
            'pays' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        // Hashage du mot de passe si présent
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $this->userService->update($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => new UserResource($user->fresh()),
        ]);
    }
}
