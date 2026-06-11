<?php

declare(strict_types=1);

namespace Modules\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Security\Services\SessionTrackingService;

/**
 * Middleware de journalisation des activités utilisateur
 */
class LogUserActivity
{
    /**
     * @var SessionTrackingService
     */
    protected SessionTrackingService $sessionTracking;

    /**
     * Constructeur
     */
    public function __construct(SessionTrackingService $sessionTracking)
    {
        $this->sessionTracking = $sessionTracking;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->user() && $request->isMethod('get')) {
            $sessionId = $request->session()->getId();
            $this->sessionTracking->updateActivity($sessionId);
        }

        return $response;
    }
}
