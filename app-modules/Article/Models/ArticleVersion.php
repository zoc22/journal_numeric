<?php

declare(strict_types=1);

namespace Modules\Article\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * Modèle ArticleVersion
 *
 * Représente une version spécifique d'un article.
 * Permet le versioning et la restauration.
 *
 * @property string $id
 * @property string $article_id
 * @property int $numero_version
 * @property string $titre
 * @property string $contenu
 * @property string|null $resume
 * @property string|null $image_principale
 * @property array|null $galerie_medias
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string $cree_par
 * @property string|null $resume_modifications
 * @property array|null $changements_detailles
 * @property bool $est_publiee
 * @property bool $est_actuelle
 */
class ArticleVersion extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'article_versions';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'article_id',
        'numero_version',
        'titre',
        'contenu',
        'resume',
        'image_principale',
        'galerie_medias',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'cree_par',
        'resume_modifications',
        'changements_detailles',
        'est_publiee',
        'est_actuelle',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'galerie_medias' => 'array',
        'changements_detailles' => 'array',
        'numero_version' => 'integer',
        'est_publiee' => 'boolean',
        'est_actuelle' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'article parent
     *
     * @return BelongsTo
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relation avec le créateur de la version
     *
     * @return BelongsTo
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    /**
     * Restaure cette version comme version actuelle
     *
     * @param User $utilisateur
     * @return bool
     */
    public function restaurer(User $utilisateur): bool
    {
        $article = $this->article;

        // Copie le contenu de cette version vers l'article
        $article->titre = $this->titre;
        $article->contenu = $this->contenu;
        $article->resume = $this->resume;
        $article->image_principale = $this->image_principale;
        $article->galerie_medias = $this->galerie_medias;
        $article->meta_title = $this->meta_title;
        $article->meta_description = $this->meta_description;
        $article->meta_keywords = $this->meta_keywords;

        // Incrémente le numéro de version
        $article->version_numero = $article->versions()->max('numero_version') + 1;

        if ($article->save()) {
            // Crée une nouvelle version basée sur la restauration
            $article->creerNouvelleVersion($utilisateur, "Restauration de la version {$this->numero_version}");

            return true;
        }

        return false;
    }

    /**
     * Compare cette version avec une autre
     *
     * @param ArticleVersion $autre
     * @return array
     */
    public function comparer(ArticleVersion $autre): array
    {
        $differences = [];

        // Compare les champs texte
        $champsTexte = ['titre', 'contenu', 'resume'];
        foreach ($champsTexte as $champ) {
            if ($this->$champ !== $autre->$champ) {
                $differences[$champ] = [
                    'avant' => $autre->$champ,
                    'apres' => $this->$champ,
                ];
            }
        }

        // Compare les médias
        if ($this->image_principale !== $autre->image_principale) {
            $differences['image_principale'] = [
                'avant' => $autre->image_principale,
                'apres' => $this->image_principale,
            ];
        }

        return $differences;
    }
}
