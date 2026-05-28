<?php
// app-modules/Core/Http/Middleware/RoleMiddleware.php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vérifie si l'utilisateur connecté possède un rôle spécifique.
 * Utilisation : Route::middleware('role:editeur_chef')->group(...)
 */
class RoleMiddleware
{
    /**
     * Traite la requête.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $role Le rôle requis
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole($role)) {
            abort(403, "Vous n'avez pas le rôle '{$role}'.");
        }

        return $next($request);
    }
}
