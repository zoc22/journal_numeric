<?php
// app-modules/Core/Traits/HasUuid.php

namespace Modules\Core\Traits;

use Illuminate\Support\Str;

/**
 * Ce trait permet à un modèle d'utiliser un UUID comme clé primaire.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void creating(\Closure $callback)
 */
trait HasUuid
{
    /**
     * Initialise l'événement "creating" pour générer un UUID.
     *
     * @return void
     */
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            // Vérifie que la clé primaire est vide avant d'attribuer un UUID
            $keyName = $model->getKeyName();
            if (empty($model->{$keyName})) {
                $model->{$keyName} = (string) Str::uuid();
            }
        });
    }

    /**
     * Indique que la clé primaire n'est pas auto-incrémentée.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Spécifie le type de la clé primaire (string pour UUID).
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
