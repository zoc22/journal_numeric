<?php
// app-modules/Core/Traits/HasAuditLog.php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Ce trait permet d'enregistrer automatiquement les actions importantes
 * sur un modèle (création, modification, suppression).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void created(\Closure $callback)
 * @method static void updated(\Closure $callback)
 * @method static void deleted(\Closure $callback)
 */
trait HasAuditLog
{
    /**
     * Enregistre l'action dans les logs.
     *
     * @param string $action Exemple : 'created', 'updated', 'deleted'
     * @param array $oldValues Les anciennes valeurs (avant modification)
     * @param array $newValues Les nouvelles valeurs
     * @return void
     */
    protected function logAudit(string $action, array $oldValues = [], array $newValues = []): void
    {
        $userId = Auth::id() ?? 'system';
        $modelName = get_class($this);
        $modelId = $this->getKey();
        $modelIdString = is_array($modelId) ? json_encode($modelId) : (string) $modelId;

        $message = "[AUDIT] Utilisateur {$userId} a {$action} le modèle {$modelName}#{$modelIdString}";

        if (!empty($oldValues)) {
            $message .= " | Ancien: " . json_encode($oldValues);
        }
        if (!empty($newValues)) {
            $message .= " | Nouveau: " . json_encode($newValues);
        }

        Log::channel('stack')->info($message);
    }

    /**
     * Initialise les événements du modèle.
     * Cette méthode doit être appelée dans la méthode boot() du modèle.
     *
     * @return void
     */
    public static function bootHasAuditLog(): void
    {
        // La méthode boot() du modèle appelle automatiquement ceci si le trait est utilisé.
        // Pour éviter les erreurs d'analyse, on utilise une variable statique.
        static::created(function ($model) {
            if (method_exists($model, 'logAudit')) {
                $model->logAudit('created', [], $model->getAttributes());
            }
        });

        static::updated(function ($model) {
            if (method_exists($model, 'logAudit')) {
                $model->logAudit('updated', $model->getOriginal(), $model->getChanges());
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'logAudit')) {
                $model->logAudit('deleted', $model->getOriginal(), []);
            }
        });
    }
}
