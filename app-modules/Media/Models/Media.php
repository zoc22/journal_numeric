<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Modèle Media
 *
 * Représente un fichier téléchargé sur la plateforme.
 * Supporte différents types de médias et l'association polymorphique.
 *
 * @property string $id
 * @property string $nom_fichier
 * @property string $nom_original
 * @property string $chemin
 * @property string $disque
 * @property string $type_mime
 * @property string $extension
 * @property int $taille
 * @property string $hash
 * @property string $type
 * @property array|null $metadonnees
 * @property array|null $variants
 * @property string|null $maison_id
 * @property string $televerse_par
 * @property bool $est_publique
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Media extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'media';

    /**
     * Attributs assignables en masse
     */
    protected $fillable = [
        'nom_fichier',
        'nom_original',
        'chemin',
        'disque',
        'type_mime',
        'extension',
        'taille',
        'hash',
        'type',
        'metadonnees',
        'variants',
        'continent',
        'pays',
        'ville',
        'maison_id',
        'televerse_par',
        'est_publique',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'metadonnees' => 'array',
        'variants' => 'array',
        'taille' => 'integer',
        'est_publique' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a téléversé le média
     */
    public function televerseur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'televerse_par');
    }

    /**
     * Relation avec les articles via la table pivot
     */
    public function articles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_media', 'media_id', 'article_id')
                    ->withPivot('type_usage', 'metadonnees', 'ordre_affichage', 'est_actif')
                    ->withTimestamps();
    }

    /**
     * Scope pour les médias d'un type spécifique
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les médias publics
     */
    public function scopePublics(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('est_publique', true);
    }

    /**
     * Scope pour une maison d'édition spécifique
     */
    public function scopePourMaison(\Illuminate\Database\Eloquent\Builder $query, ?string $maisonId): \Illuminate\Database\Eloquent\Builder
    {
        if ($maisonId) {
            return $query->where('maison_id', $maisonId);
        }
        return $query;
    }

    /**
     * Obtient l'URL complète du média
     */
    public function getUrlAttribute(): string
    {
        $cdnUrl = config('media.urls.cdn_url');

        if ($cdnUrl) {
            return $cdnUrl . '/' . $this->chemin;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disque);
        
        return $disk->url($this->chemin);
    }

    /**
     * Obtient l'URL d'une variante (optimisée)
     */
    public function getVariantUrl(string $variant): ?string
    {
        if (!$this->variants || !isset($this->variants[$variant])) {
            return null;
        }

        $cdnUrl = config('media.urls.cdn_url');

        if ($cdnUrl) {
            return $cdnUrl . '/' . $this->variants[$variant];
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disque);

        return $disk->url($this->variants[$variant]);
    }

    /**
     * Obtient la taille formatée (ex: "1.5 MB")
     */
    public function getTailleFormateeAttribute(): string
    {
        $bytes = $this->taille;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Vérifie si le média est une image
     */
    public function estImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Vérifie si le média est un document
     */
    public function estDocument(): bool
    {
        return $this->type === 'document';
    }

    /**
     * Vérifie si le média est une vidéo
     */
    public function estVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Obtient les dimensions de l'image si disponible
     */
    public function getDimensionsAttribute(): ?array
    {
        if (!$this->estImage() || !$this->metadonnees) {
            return null;
        }

        return [
            'width' => $this->metadonnees['width'] ?? null,
            'height' => $this->metadonnees['height'] ?? null,
        ];
    }

    /**
     * Supprime le fichier physique lors de la suppression du modèle
     */
    protected static function booted(): void
    {
        static::deleting(function ($media) {
            // Supprime le fichier original
            Storage::disk($media->disque)->delete($media->chemin);

            // Supprime les variantes si elles existent
            if ($media->variants) {
                foreach ($media->variants as $variant) {
                    Storage::disk($media->disque)->delete($variant);
                }
            }
        });
    }
}
