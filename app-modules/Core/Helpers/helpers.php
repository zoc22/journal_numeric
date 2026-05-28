<?php
// app-modules/Core/Helpers/helpers.php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Aide : génère un UUID (Universally Unique Identifier).
 *
 * @return string
 */
if (!function_exists('uuid')) {
    function uuid(): string
    {
        return (string) Str::uuid();
    }
}

/**
 * Aide : formate un montant en devise (exemple : 1000 → "10,00 €").
 *
 * @param float $amount
 * @param string $currency
 * @return string
 */
if (!function_exists('format_currency')) {
    function format_currency(float $amount, string $currency = 'EUR'): string
    {
        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }
}

/**
 * Aide : retourne le chemin absolu d'un module.
 */

if (!function_exists('module_path')) {
    function module_path(string $module, string $path = ''): string
    {
        return base_path("app-modules/{$module}/{$path}");
    }
}

/**
 * Aide : récupère la configuration d'un module (avec cache).
 *
 * @param string $moduleName
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('module_config')) {
    function module_config(string $moduleName, string $key, $default = null)
    {
        $moduleNameLower = Str::lower($moduleName);
        $cacheKey = "module.{$moduleNameLower}.config.{$key}";
        return Cache::remember($cacheKey, 3600, function () use ($moduleNameLower, $key, $default) {
            $config = config("{$moduleNameLower}.{$key}");
            return $config ?? $default;
        });
    }
}
