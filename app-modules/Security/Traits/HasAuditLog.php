<?php

declare(strict_types=1);

namespace Modules\Security\Traits;

use Modules\Security\Services\AuditService;

/**
 * Trait HasAuditLog
 *
 * Ajoute des méthodes d'audit aux modèles.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasAuditLog
{
    /**
     * Enregistre une action d'audit pour ce modèle
     *
     * @param string $action
     * @param array $options
     * @return void
     */
    public function audit(string $action, array $options = []): void
    {
        $auditService = app(AuditService::class);

        $auditService->log($action, $options['module'] ?? $this->getAuditModule(), [
            'entite_type' => get_class($this),
            'entite_id' => $this->getKey(),
            'anciennes_valeurs' => $options['anciennes'] ?? ($this->getOriginal() ?: null),
            'nouvelles_valeurs' => $options['nouvelles'] ?? ($this->getAttributes() ?: null),
            'description' => $options['description'] ?? null,
            'metadonnees' => $options['metadonnees'] ?? null,
            'niveau' => $options['niveau'] ?? 'info',
        ]);
    }

    /**
     * Enregistre la création de l'entité
     *
     * @return void
     */
    protected static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            if (config('security.logging.enabled', true)) {
                $model->audit('create', [
                    'nouvelles' => $model->getAttributes(),
                ]);
            }
        });

        static::updated(function ($model) {
            if (config('security.logging.enabled', true)) {
                $model->audit('update', [
                    'anciennes' => $model->getOriginal(),
                    'nouvelles' => $model->getAttributes(),
                ]);
            }
        });

        static::deleted(function ($model) {
            if (config('security.logging.enabled', true)) {
                $model->audit('delete', [
                    'anciennes' => $model->getOriginal(),
                    'niveau' => 'warning',
                ]);
            }
        });
    }

    /**
     * Obtient le module pour l'audit
     *
     * @return string
     */
    protected function getAuditModule(): string
    {
        return strtolower(class_basename($this));
    }
}
