<?php
// app-modules/Core/Http/Middleware/PermissionMiddleware.php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vérifie si l'utilisateur connecté possède une permission spécifique.
 * Utilisation : Route::middleware('permission:article.create')->group(...)
 */
class PermissionMiddleware
{
    /**
     * Traite la requête entrante.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permission La permission requise (ex: 'article.submit')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = Auth::user();

        // Si l'utilisateur n'est pas connecté ou n'a pas la permission, on bloque.
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, "Vous n'avez pas la permission '{$permission}'.");
        }

        return $next($request);
    }
}
