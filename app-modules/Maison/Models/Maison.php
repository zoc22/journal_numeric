<?php

declare(strict_types=1);

namespace Modules\Maison\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Traits\HasUuid;
use Modules\User\Models\User;

use Illuminate\Database\Eloquent\Casts\Attribute;

// À décommenter quand les modules seront créés
// use Modules\Article\Models\Article;
// use Modules\Recruitment\Models\AppelCandidature;

/**
 * Classe représentant une maison d'édition (tenant).
 * 
 * @property string $id
 * @property string $nom
 * @property string $slug
 * @property string $description
 * @property string|null $logo_url
 * @property string $email_contact
 * @property string $statut
 * @property string $statut_libelle
 */
class Maison extends Model
{
    use HasFactory;
    use HasTimestamps;
    use HasUuid;
    use HasAuditLog;

    /**
     * Boot du modèle.
     */
    protected static function boot()
    {
        parent::boot();

        // Enregistrement de l'observer
        static::observe(\Modules\Maison\Observers\MaisonObserver::class);
    }

    /**
     * Le nom de la table associée à ce modèle.
     *
     * @var string
     */
    protected $table = 'maisons';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'logo_url',
        'email_contact',
        'statut',
        'continent',
        'pays',
        'ville',
        'validee_par',
        'validee_le',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validee_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les attributs avec leurs valeurs par défaut.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => 'en_attente',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Relation : L'administrateur plateforme qui a validé cette maison.
     *
     * @return BelongsTo
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validee_par');
    }

    /**
     * Relation : Les membres de cette maison (relation pivot).
     *
     * @return HasMany
     */
    public function membres(): HasMany
    {
        return $this->hasMany(MembreMaison::class, 'maison_id');
    }

    /**
     * Relation : Les utilisateurs membres de cette maison (via pivot).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function utilisateurs()
    {
        return $this->belongsToMany(
            User::class,
            'membre_maison',
            'maison_id',
            'utilisateur_id'
        )->withPivot('role_id', 'est_actif', 'a_rejoint_le', 'a_quitte_le')
         ->withTimestamps();
    }

    /**
     * Relation : Les articles publiés par cette maison.
     * À décommenter quand le module Article sera créé.
     *
     * @return HasMany
     */
    // public function articles(): HasMany
    // {
    //     return $this->hasMany(Article::class, 'maison_id');
    // }

    /**
     * Relation : Les appels à candidatures publiés par cette maison.
     * À décommenter quand le module Recruitment sera créé.
     *
     * @return HasMany
     */
    // public function appelsCandidature(): HasMany
    // {
    //     return $this->hasMany(AppelCandidature::class, 'maison_id');
    // }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope : Filtrer les maisons actives.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActives($query)
    {
        return $query->where('statut', 'active');
    }

    /**
     * Scope : Filtrer les maisons en attente de validation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope : Filtrer les maisons suspendues.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspendues($query)
    {
        return $query->where('statut', 'suspendue');
    }

    /**
     * Scope : Rechercher par nom ou slug.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('nom', 'LIKE', "%{$search}%")
                     ->orWhere('slug', 'LIKE', "%{$search}%")
                     ->orWhere('email_contact', 'LIKE', "%{$search}%");
    }

    // =========================================================================
    // MÉTHODES MÉTIER
    // =========================================================================

    /**
     * Accesseur : Récupère le libellé du statut.
     *
     * @return Attribute
     */
    protected function statutLibelle(): Attribute
    {
        return Attribute::get(function () {
            $statuts = config('module.maison.statuts', []);
            return $statuts[$this->statut] ?? $this->statut;
        });
    }

    /**
     * Active la maison d'édition.
     *
     * @param string $valideurId ID de l'admin qui valide
     * @return bool
     */
    public function activer(string $valideurId): bool
    {
        if (!in_array($this->statut, ['en_attente', 'suspendue'])) {
            return false;
        }

        $this->statut = 'active';
        $this->validee_par = $valideurId;
        $this->validee_le = now();

        return $this->save();
    }

    /**
     * Suspend la maison d'édition.
     *
     * @return bool
     */
    public function suspendre(): bool
    {
        if ($this->statut !== 'active') {
            return false;
        }

        $this->statut = 'suspendue';
        return $this->save();
    }

    /**
     * Rejette la demande de création de la maison.
     *
     * @param string $valideurId ID de l'admin qui rejette
     * @return bool
     */
    public function rejeter(string $valideurId): bool
    {
        if ($this->statut !== 'en_attente') {
            return false;
        }

        $this->statut = 'rejetee';
        $this->validee_par = $valideurId;
        $this->validee_le = now();

        return $this->save();
    }

    /**
     * Vérifie si la maison est validée et active.
     *
     * @return bool
     */
    public function estValidee(): bool
    {
        return $this->statut === 'active';
    }

    /**
     * Vérifie si la maison est en attente de validation.
     *
     * @return bool
     */
    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    /**
     * Vérifie si la maison est suspendue.
     *
     * @return bool
     */
    public function estSuspendue(): bool
    {
        return $this->statut === 'suspendue';
    }

    /**
     * Génère un slug unique à partir du nom.
     *
     * @param string $nom
     * @return string
     */
    public static function generateSlug(string $nom): string
    {
        $slug = \Illuminate\Support\Str::slug($nom);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Vérifie si un utilisateur est membre de cette maison.
     *
     * @param string $userId
     * @return bool
     */
    public function estMembre(string $userId): bool
    {
        return $this->membres()
            ->where('utilisateur_id', $userId)
            ->where('est_actif', true)
            ->exists();
    }

    /**
     * Vérifie si un utilisateur a un rôle spécifique dans cette maison.
     *
     * @param string $userId
     * @param string $roleName
     * @return bool
     */
    public function aLeRole(string $userId, string $roleName): bool
    {
        return $this->membres()
            ->where('utilisateur_id', $userId)
            ->whereHas('role', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->where('est_actif', true)
            ->exists();
    }

    /**
     * Récupère le niveau hiérarchique d'un utilisateur dans cette maison.
     *
     * @param string $userId
     * @return int|null
     */
    public function getNiveauHierarchique(string $userId): ?int
    {
        $membre = $this->membres()
            ->where('utilisateur_id', $userId)
            ->where('est_actif', true)
            ->with('role')
            ->first();

        if (!$membre || !$membre->role) {
            return null;
        }

        return $membre->role->level ?? null;
    }
}
