<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Article\Events\ArticleArchived;
use Modules\Article\Events\ArticleCreated;
use Modules\Article\Events\ArticlePublished;
use Modules\Article\Events\ArticleRejected;
use Modules\Article\Events\ArticleSubmitted;
use Modules\Article\Events\ArticleUpdated;
use Modules\Article\Services\VersionService;
use Modules\Core\Traits\HasAuditLog;
use Modules\User\Models\User;
use Modules\Workflow\Models\ReviewAssignment;
use Modules\Workflow\Models\WorkflowTransition;

/**
 * Modèle Article
 *
 * Représente un contenu éditorial avec support de :
 * - Versioning complet
 * - Workflow éditorial multi-niveaux
 * - Métriques d'engagement
 * - SEO
 * - Multi-tenant
 *
 * @property string $id
 * @property string $titre
 * @property string $slug
 * @property string $contenu
 * @property string|null $resume
 * @property string|null $image_principale
 * @property array|null $galerie_medias
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property int $nb_vues
 * @property int $nb_partages
 * @property int $nb_likes
 * @property int $nb_commentaires
 * @property int|null $temps_lecture
 * @property string|null $maison_id
 * @property string $auteur_id
 * @property string|null $version_actuelle_id
 * @property string $statut
 * @property int $version_numero
 * @property string|null $publie_le
 * @property string|null $archive_le
 */
class Article extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use HasAuditLog;

    /**
     * Nom de la table associée
     */
    protected $table = 'articles';

    /**
     * Attributs assignables en masse
     */
    protected $fillable = [
        'titre',
        'slug',
        'contenu',
        'resume',
        'image_principale',
        'galerie_medias',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'temps_lecture',
        'continent',
        'pays',
        'ville',
        'maison_id',
        'auteur_id',
        'statut',
        'version_numero',
        'publie_le',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'galerie_medias' => 'array',
        'nb_vues' => 'integer',
        'nb_partages' => 'integer',
        'nb_likes' => 'integer',
        'nb_commentaires' => 'integer',
        'version_numero' => 'integer',
        'temps_lecture' => 'integer',
        'publie_le' => 'datetime',
        'archive_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Attributs protégés
     */
    protected $guarded = [
        'id',
        'nb_vues',
        'nb_partages',
        'nb_likes',
        'nb_commentaires',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Événements du modèle
     */
    protected $dispatchesEvents = [
        'created' => ArticleCreated::class,
        'updated' => ArticleUpdated::class,
    ];

    /**
     * Relation avec l'auteur
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function auteur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /**
     * Relation avec les versions
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleVersion::class, 'article_id')
                    ->orderBy('numero_version', 'desc');
    }

    /**
     * Relation avec la version actuelle
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function versionActuelle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ArticleVersion::class, 'version_actuelle_id');
    }

    /**
     * Relation avec les catégories
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category', 'article_id', 'categorie_id')
                    ->withTimestamps()
                    ->orderBy('categories.ordre');
    }

    /**
     * Relation avec les tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag')
                    ->withTimestamps();
    }

    /**
     * Relation avec les transitions de workflow
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transitionsWorkflow(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(WorkflowTransition::class, 'workflowable')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Relation avec les assignations de review
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviewAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'article_id')
                    ->with(['reviewer', 'feedback']);
    }

    /**
     * Scope pour les articles publiés
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublies($query)
    {
        return $query->where('statut', 'publie')
                     ->whereNotNull('publie_le');
    }

    /**
     * Scope pour les articles récents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecents($query, int $limit = 10)
    {
        return $query->publies()
                     ->orderBy('publie_le', 'desc')
                     ->limit($limit);
    }

    /**
     * Incrémente le compteur de vues
     *
     * @return self
     */
    public function incrementVues(): self
    {
        $this->increment('nb_vues');

        // Nettoie le cache de l'article
        Cache::forget("article.{$this->id}");

        return $this;
    }

    /**
     * Incrémente le compteur de partages
     *
     * @return self
     */
    public function incrementPartages(): self
    {
        $this->increment('nb_partages');

        return $this;
    }

    /**
     * Incrémente le compteur de likes
     *
     * @return self
     */
    public function incrementLikes(): self
    {
        $this->increment('nb_likes');

        return $this;
    }

    /**
     * Vérifie si l'article est publié
     *
     * @return bool
     */
    public function estPublie(): bool
    {
        return $this->statut === 'publie' && !is_null($this->publie_le);
    }

    /**
     * Vérifie si l'article est en brouillon
     *
     * @return bool
     */
    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    /**
     * Vérifie si l'article peut être modifié
     *
     * @return bool
     */
    public function estModifiable(): bool
    {
        return in_array($this->statut, ['brouillon', 'correction_demandee']);
    }

    /**
     * Vérifie si l'article est en cours de relecture
     *
     * @return bool
     */
    public function estEnRelecture(): bool
    {
        return $this->statut === 'en_relecture';
    }

    /**
     * Vérifie si l'article est validé
     *
     * @return bool
     */
    public function estValide(): bool
    {
        return in_array($this->statut, [
            'valide_reviewer',
            'valide_editeur',
            'valide_directeur',
            'valide_final'
        ]);
    }

    /**
     * Génère un slug unique à partir du titre
     *
     * @param string $titre
     * @param string|null $ignoreId
     * @return string
     */
    public static function genererSlug(string $titre, ?string $ignoreId = null): string
    {
        $slug = \Illuminate\Support\Str::slug($titre, '-');
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
     * Calcule le temps de lecture estimé
     *
     * @param string|null $contenu
     * @return int
     */
    public static function calculerTempsLecture(?string $contenu): int
    {
        if (empty($contenu)) {
            return 1;
        }

        // Supprime les balises HTML et compte les mots
        $texteBrut = strip_tags($contenu);
        $nbMots = str_word_count($texteBrut);

        // Vitesse de lecture moyenne : 200 mots par minute
        $tempsLecture = ceil($nbMots / 200);

        return max(1, $tempsLecture);
    }

    /**
     * Crée une nouvelle version de l'article
     *
     * @param User $auteur
     * @param string|null $resumeModifications
     * @return ArticleVersion
     */
    public function creerNouvelleVersion(User $auteur, ?string $resumeModifications = null): ArticleVersion
    {
        $versionService = app(VersionService::class);
        return $versionService->creerVersion($this, $auteur, $resumeModifications);
    }

    /**
     * Publie l'article
     *
     * @param User $editeur
     * @return bool
     */
    public function publier(User $editeur): bool
    {
        if ($this->estPublie()) {
            return false;
        }

        $ancienStatut = $this->statut;
        $this->statut = 'publie';
        $this->publie_le = now();

        if ($this->save()) {
            event(new ArticlePublished($this, $editeur, $ancienStatut));

            // Crée une version publiée
            $this->creerNouvelleVersion($editeur, 'Article publié');

            return true;
        }

        return false;
    }

    /**
     * Archive l'article
     *
     * @param User $utilisateur
     * @return bool
     */
    public function archiver(User $utilisateur): bool
    {
        if ($this->statut === 'archive') {
            return false;
        }

        $ancienStatut = $this->statut;
        $this->statut = 'archive';
        $this->archive_le = now();

        if ($this->save()) {
            event(new ArticleArchived($this, $utilisateur, $ancienStatut));
            return true;
        }

        return false;
    }

    /**
     * Soumet l'article pour relecture
     *
     * @param User $journaliste
     * @param string|null $note
     * @return bool
     */
    public function soumettre(User $journaliste, ?string $note = null): bool
    {
        if ($this->statut !== 'brouillon') {
            return false;
        }

        $ancienStatut = $this->statut;
        $this->statut = 'soumis';

        if ($this->save()) {
            event(new ArticleSubmitted($this, $journaliste, $note, $ancienStatut));

            // Crée une version avant soumission
            $this->creerNouvelleVersion($journaliste, 'Soumission pour relecture');

            return true;
        }

        return false;
    }

    /**
     * Rejette l'article
     *
     * @param User $reviewer
     * @param string $raison
     * @return bool
     */
    public function rejeter(User $reviewer, string $raison): bool
    {
        if (in_array($this->statut, ['publie', 'archive', 'rejete'])) {
            return false;
        }

        $ancienStatut = $this->statut;
        $this->statut = 'rejete';

        if ($this->save()) {
            event(new ArticleRejected($this, $reviewer, $raison, $ancienStatut));
            return true;
        }

        return false;
    }

    /**
     * Obtient l'URL de l'article
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return route('articles.show', [
            'article' => $this->id,
            'slug' => $this->slug
        ]);
    }

    /**
     * Obtient le temps de lecture formaté
     *
     * @return string
     */
    public function getTempsLectureFormateAttribute(): string
    {
        $minutes = $this->temps_lecture ?? self::calculerTempsLecture($this->contenu);

        if ($minutes < 1) {
            return '< 1 min';
        }

        return $minutes . ' min de lecture';
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        // Événement de création : génère le slug si nécessaire
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = self::genererSlug($article->titre);
            }

            if (empty($article->temps_lecture) && !empty($article->contenu)) {
                $article->temps_lecture = self::calculerTempsLecture($article->contenu);
            }
        });

        // Événement de mise à jour : met à jour le slug si le titre change
        static::updating(function ($article) {
            if ($article->isDirty('titre') && !$article->isDirty('slug')) {
                $article->slug = self::genererSlug($article->titre, $article->id);
            }

            if ($article->isDirty('contenu') && !$article->isDirty('temps_lecture')) {
                $article->temps_lecture = self::calculerTempsLecture($article->contenu);
            }
        });

        // Événement de suppression : met à jour les relations
        static::deleting(function ($article) {
            $article->categories()->detach();
            $article->tags()->detach();
        });
    }
}
