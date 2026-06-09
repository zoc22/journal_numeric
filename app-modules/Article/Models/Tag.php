<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle Tag
 *
 * Tag pour le marquage sémantique des articles.
 * Utilisé pour les recherches et recommandations.
 *
 * @property string $id
 * @property string $nom
 * @property string $slug
 * @property string|null $description
 * @property string|null $maison_id
 */
class Tag extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'tags';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'maison_id',
    ];

    /**
     * Relation avec les articles
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag')
                    ->withTimestamps();
    }

    /**
     * Génère un slug unique
     *
     * @param string $nom
     * @param string|null $ignoreId
     * @return string
     */
    public static function genererSlug(string $nom, ?string $ignoreId = null): string
    {
        $slug = \Illuminate\Support\Str::slug($nom, '-');
        $originalSlug = $slug;
        $count = 1;

        $query = static::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $count++;
            $query = static::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = self::genererSlug($tag->nom);
            }
        });
    }
}
