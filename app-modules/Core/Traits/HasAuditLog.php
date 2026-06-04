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
     * Cette méthode est appelée automatiquement lors de la création, modification
     * ou suppression d'un modèle qui utilise ce trait.
     *
     * @param string $action L'action effectuée (ex : 'created', 'updated', 'deleted')
     * @param array<string, mixed> $oldValues Les anciennes valeurs avant modification (vide pour create)
     * @param array<string, mixed> $newValues Les nouvelles valeurs après modification
     * @return void
     */
    protected function logAudit(string $action, array $oldValues = [], array $newValues = []): void
    {
        // Récupère l'ID de l'utilisateur connecté ou "system" si déconnecté
        $userId = Auth::id() ?? 'system';

        // Récupère le nom de la classe du modèle (ex : App\Models\User)
        $modelName = get_class($this);

        // Récupère la clé primaire du modèle
        $modelId = $this->getKey();

        // Convertit la clé primaire en string (utile pour les UUIDs ou clés composées)
        $modelIdString = is_array($modelId) ? json_encode($modelId) : (string) $modelId;

        // Construit le message d'audit de base
        $message = "[AUDIT] Utilisateur {$userId} a {$action} le modèle {$modelName}#{$modelIdString}";

        // Ajoute les anciennes valeurs si présentes
        if (!empty($oldValues)) {
            $message .= " | Ancien: " . json_encode($oldValues);
        }

        // Ajoute les nouvelles valeurs si présentes
        if (!empty($newValues)) {
            $message .= " | Nouveau: " . json_encode($newValues);
        }

        // Enregistre le message dans les logs (utilise le canal 'stack' par défaut)
        Log::channel('stack')->info($message);
    }

    /**
     * Initialise les événements du modèle.
     *
     * Cette méthode doit être appelée dans la méthode boot() du modèle
     * pour activer la journalisation automatique.
     *
     * Elle enregistre les écouteurs pour les événements :
     * - created : quand un nouvel enregistrement est créé
     * - updated : quand un enregistrement est modifié
     * - deleted : quand un enregistrement est supprimé (soft delete)
     *
     * @return void
     */
    public static function bootHasAuditLog(): void
    {
        // Écouteur pour l'événement "created"
        // Enregistre la création d'un nouvel enregistrement
        static::created(function ($model) {
            if (method_exists($model, 'logAudit')) {
                // Enregistre la création avec les attributs du nouvel enregistrement
                $model->logAudit('created', [], $model->getAttributes());
            }
        });

        // Écouteur pour l'événement "updated"
        // Enregistre les modifications d'un enregistrement
        static::updated(function ($model) {
            if (method_exists($model, 'logAudit')) {
                // Enregistre la modification avec les valeurs originales et les changements
                $model->logAudit('updated', $model->getOriginal(), $model->getChanges());
            }
        });

        // Écouteur pour l'événement "deleted"
        // Enregistre la suppression d'un enregistrement (soft delete)
        static::deleted(function ($model) {
            if (method_exists($model, 'logAudit')) {
                // Enregistre la suppression avec les valeurs originales
                $model->logAudit('deleted', $model->getOriginal(), []);
            }
        });
    }
}
