<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Core\Traits\HasAuditLog;

/**
 * Modèle Category
 *
 * Représente une catégorie hiérarchique pour organiser les articles.
 * Supporte une arborescence infinie avec chemin stocké pour performances.
 *
 * @property string $id
 * @property string $nom
 * @property string $slug
 * @property string|null $description
 * @property string|null $parent_id
 * @property string|null $chemin
 * @property int $niveau
 * @property string|null $icone
 * @property string|null $couleur
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $ordre
 * @property string|null $maison_id
 * @property bool $est_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Category extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditLog;

    /**
     * Nom de la table
     */
    protected $table = 'categories';

    /**
     * Attributs assignables en masse
     */
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'parent_id',
        'chemin',
        'niveau',
        'icone',
        'couleur',
        'meta_title',
        'meta_description',
        'ordre',
        'maison_id',
        'est_active',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'niveau' => 'integer',
        'ordre' => 'integer',
        'est_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec la catégorie parente
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relation avec les catégories enfants
     */
    public function enfants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('ordre');
    }

    /**
     * Relation avec les articles
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category', 'categorie_id', 'article_id')
                    ->withTimestamps();
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActives(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('est_active', true);
    }

    /**
     * Scope pour les catégories racines
     */
    public function scopeRacines(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour une maison d'édition spécifique
     */
    public function scopePourMaison(\Illuminate\Database\Eloquent\Builder $query, ?string $maisonId)
    {
        if ($maisonId) {
            return $query->where('maison_id', $maisonId);
        }
        return $query;
    }

    /**
     * Génère un slug unique
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
     * Met à jour le chemin hiérarchique
     */
    public function mettreAJourChemin(): self
    {
        if ($this->parent_id) {
            $parent = self::find($this->parent_id);
            if ($parent) {
                $this->chemin = $parent->chemin . '/' . $this->id;
                $this->niveau = $parent->niveau + 1;
            } else {
                $this->chemin = $this->id;
                $this->niveau = 0;
            }
        } else {
            $this->chemin = $this->id;
            $this->niveau = 0;
        }

        $this->saveQuietly();

        // Met à jour le chemin des enfants récursivement
        foreach ($this->enfants as $enfant) {
            $enfant->mettreAJourChemin();
        }

        return $this;
    }

    /**
     * Obtient tous les descendants de la catégorie
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->enfants as $enfant) {
            $descendants->push($enfant);
            $descendants = $descendants->merge($enfant->getDescendants());
        }

        return $descendants;
    }

    /**
     * Obtient tous les IDs des descendants
     */
    public function getDescendantIds(): array
    {
        return $this->getDescendants()->pluck('id')->toArray();
    }

    /**
     * Obtient le chemin complet sous forme de tableau
     */
    public function getCheminCompletAttribute(): array
    {
        if (!$this->chemin) {
            return [];
        }

        $ids = explode('/', $this->chemin);

        return self::whereIn('id', $ids)
                    ->orderBy('niveau')
                    ->get()
                    ->toArray();
    }

    /**
     * Vérifie si la catégorie a des enfants
     */
    public function hasEnfants(): bool
    {
        return $this->enfants()->count() > 0;
    }

    /**
     * Vérifie si la catégorie est une catégorie racine
     */
    public function isRacine(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        // Événement de création
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = self::genererSlug($category->nom);
            }
            if (!isset($category->ordre)) {
                $category->ordre = 0;
            }
        });

        // Événement de mise à jour
        static::updating(function ($category) {
            if ($category->isDirty('nom') && !$category->isDirty('slug')) {
                $category->slug = self::genererSlug($category->nom, $category->id);
            }
        });

        // Événement après sauvegarde pour mettre à jour le chemin
        static::saved(function ($category) {
            $category->mettreAJourChemin();
        });
    }
}
