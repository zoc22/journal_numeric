<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Article\Models\Article;

/**
 * Modèle ArticleMedia (Pivot)
 *
 * Représente la relation entre un article et un média avec
 * des métadonnées contextuelles.
 *
 * @property string $id
 * @property string $article_id
 * @property string $media_id
 * @property string $type_usage
 * @property array|null $metadonnees
 * @property int $ordre_affichage
 * @property bool $est_actif
 */
class ArticleMedia extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'article_media';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'article_id',
        'media_id',
        'type_usage',
        'metadonnees',
        'ordre_affichage',
        'est_actif',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'metadonnees' => 'array',
        'ordre_affichage' => 'integer',
        'est_actif' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relation avec le média
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Scope pour les médias de couverture
     */
    public function scopeCouverture(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type_usage', 'couverture');
    }

    /**
     * Scope pour les médias actifs
     */
    public function scopeActifs(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('est_actif', true);
    }

    /**
     * Obtient la légende du média
     */
    public function getLegendeAttribute(): ?string
    {
        return $this->metadonnees['legende'] ?? null;
    }

    /**
     * Obtient les crédits du média
     */
    public function getCreditsAttribute(): ?string
    {
        return $this->metadonnees['credits'] ?? null;
    }
}
