<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

/**
 * Service de rate limiting
 *
 * Gère les limitations de taux pour les différentes actions.
 */
class RateLimitingService
{
    /**
     * @var RateLimiter
     */
    protected RateLimiter $rateLimiter;

    /**
     * Constructeur
     */
    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Vérifie si une action peut être effectuée
     *
     * @param string $key
     * @param string $type
     * @return bool
     */
    public function attempt(string $key, string $type = 'api'): bool
    {
        if (!config('security.rate_limiting.enabled', true)) {
            return true;
        }

        $maxAttempts = $this->getMaxAttempts($type);
        $decayMinutes = $this->getDecayMinutes($type);

        if ($this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $this->rateLimiter->hit($key, $decayMinutes * 60);

        return true;
    }

    /**
     * Récupère le nombre de tentatives restantes
     *
     * @param string $key
     * @param string $type
     * @return int
     */
    public function remainingAttempts(string $key, string $type = 'api'): int
    {
        $maxAttempts = $this->getMaxAttempts($type);
        $attempts = $this->rateLimiter->attempts($key);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Récupère le temps avant réinitialisation (en secondes)
     *
     * @param string $key
     * @return int
     */
    public function availableIn(string $key): int
    {
        return $this->rateLimiter->availableIn($key);
    }

    /**
     * Réinitialise le compteur pour une clé
     *
     * @param string $key
     * @return void
     */
    public function clear(string $key): void
    {
        $this->rateLimiter->clear($key);
    }

    /**
     * Vérifie si trop de tentatives ont été effectuées
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->rateLimiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Enregistre une tentative
     */
    public function hit(string $key, int $decaySeconds = 60): void
    {
        $this->rateLimiter->hit($key, $decaySeconds);
    }

    /**
     * Obtient le nombre maximum de tentatives pour un type
     */
    protected function getMaxAttempts(string $type): int
    {
        $config = config('security.rate_limiting.max_attempts', ['api' => 60]);

        return $config[$type] ?? $config['api'] ?? 60;
    }

    /**
     * Obtient les minutes de décroissance pour un type
     *
     * @param string $type
     * @return int
     */
    protected function getDecayMinutes(string $type): int
    {
        $config = config('security.rate_limiting.decay_minutes', ['api' => 1]);

        return $config[$type] ?? $config['api'] ?? 1;
    }

    /**
     * Limiteur pour les tentatives de connexion
     *
     * @param Request $request
     * @return bool
     */
    public function limitLogin(Request $request): bool
    {
        $key = 'login:' . $request->ip();

        return $this->attempt($key, 'login');
    }

    /**
     * Limiteur pour les actions sensibles
     *
     * @param Request $request
     * @return bool
     */
    public function limitSensitiveAction(Request $request): bool
    {
        $user = $request->user();
        $key = 'sensitive:' . ($user?->id ?? $request->ip());

        return $this->attempt($key, 'sensitive');
    }
}
