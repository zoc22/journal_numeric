<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirige l'utilisateur vers la page d'authentification de Google.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Gère le retour de l'authentification Google.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            // Recherche de l'utilisateur par son email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Création d'un nouvel utilisateur si non trouvé
                $user = User::create([
                    'nom' => $googleUser->getName() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'avatar' => $googleUser->getAvatar(),
                    'is_active' => true,
                ]);
            } else {
                // Mise à jour de l'avatar si nécessaire
                $user->update([
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            // Authentification de l'utilisateur
            Auth::login($user);

            // Génération d'un token Sanctum pour l'API
            $token = $user->createToken('google-auth')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Authentification réussie',
                'user' => $user,
                'token' => $token,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur d\'authentification Google',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
