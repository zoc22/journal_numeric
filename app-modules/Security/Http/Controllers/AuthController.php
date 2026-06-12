<?php

declare(strict_types=1);

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\User\Models\User;
use Modules\Security\Services\AuditService;
use Modules\Security\Services\RateLimitingService;

/**
 * Contrôleur d'authentification
 *
 * Gère la connexion, déconnexion, l'inscription et la réinitialisation de mot de passe.
 */
class AuthController extends Controller
{
    protected AuditService $auditService;
    protected RateLimitingService $rateLimitingService;

    public function __construct(AuditService $auditService, RateLimitingService $rateLimitingService)
    {
        $this->auditService = $auditService;
        $this->rateLimitingService = $rateLimitingService;
    }

    /**
     * Connexion de l'utilisateur
     */
    public function login(Request $request): JsonResponse
    {
        // Rate limiting
        if ($this->rateLimitingService->tooManyAttempts('login:' . $request->ip(), 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives de connexion. Veuillez réessayer plus tard.',
                'retry_after' => $this->rateLimitingService->availableIn('login:' . $request->ip()),
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            $this->rateLimitingService->hit('login:' . $request->ip(), 900); // 15 min lock
            $this->auditService->logLogin(null, false, $request->email, 'Identifiants invalides');
            
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects',
                'errors' => ['email' => ['Les identifiants fournis ne correspondent pas à nos enregistrements.']],
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user->is_active) {
            $this->auditService->logLogin($user, false, $request->email, 'Compte désactivé');
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé.',
            ], 403);
        }

        $this->rateLimitingService->clear('login:' . $request->ip());
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $this->auditService->logLogin($user, true);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nom' => $request->nom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->auditService->log('register', 'auth', [
            'utilisateur_id' => $user->id,
            'description' => "Nouvel utilisateur inscrit : {$user->email}",
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            $user->tokens()->delete();
            $this->auditService->log('logout', 'auth', [
                'description' => "Déconnexion de l'utilisateur {$user->email}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Envoi d'un lien de réinitialisation de mot de passe
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 422);
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 422);
    }
}
