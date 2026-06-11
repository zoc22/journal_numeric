<?php

declare(strict_types=1);

namespace Modules\Recruitment\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\CallStatus;
use App\Models\Tenant;
use Modules\User\Models\User;
use Modules\Core\Traits\HasUuid;

/**
 * Modèle CallForApplication
 *
 * Représente un appel à candidatures publié par une maison d'édition.
 *
 * @property string $id
 * @property string $titre
 * @property string $slug
 * @property string $description
 * @property array $roles_vises
 * @property string|null $conditions
 * @property array|null $prerequis
 * @property Carbon $date_limite
 * @property Carbon|null $publie_le
 * @property Carbon|null $ferme_le
 * @property string $statut
 * @property int $nombre_postes
 * @property array|null $metadonnees
 * @property string $maison_id
 * @property string $cree_par
 */
class CallForApplication extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'calls_for_applications';

    /**
     * Indique si la clé primaire est auto-incrémentée
     */
    public $incrementing = false;

    /**
     * Le type de la clé primaire
     */
    protected $keyType = 'string';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'titre',
        'slug',
        'description',
        'roles_vises',
        'conditions',
        'prerequis',
        'date_limite',
        'publie_le',
        'ferme_le',
        'continent',
        'pays',
        'ville',
        'statut',
        'nombre_postes',
        'metadonnees',
        'maison_id',
        'cree_par',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'roles_vises' => 'array',
        'prerequis' => 'array',
        'metadonnees' => 'array',
        'date_limite' => 'date',
        'publie_le' => 'datetime',
        'ferme_le' => 'datetime',
        'nombre_postes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec la maison d'édition
     */
    public function maison(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'maison_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'appel
     */
    public function createur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    /**
     * Relation avec les candidatures
     */
    public function candidatures(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Application::class, 'appel_id');
    }

    /**
     * Obtient le statut sous forme d'enum
     */
    public function getStatutEnum(): CallStatus
    {
        return CallStatus::from($this->statut);
    }

    /**
     * Obtient le label du statut
     */
    public function getStatutLabelAttribute(): string
    {
        return $this->getStatutEnum()->label();
    }

    /**
     * Vérifie si l'appel est ouvert
     */
    public function estOuvert(): bool
    {
        return $this->statut === CallStatus::OUVERT->value;
    }

    /**
     * Vérifie si l'appel est expiré
     */
    public function estExpire(): bool
    {
        return $this->date_limite->isPast();
    }

    /**
     * Vérifie si l'appel accepte encore les candidatures
     */
    public function accepteCandidatures(): bool
    {
        return $this->statut === CallStatus::OUVERT->value && !$this->estExpire();
    }

    /**
     * Publie l'appel
     */
    public function publier(): bool
    {
        if ($this->statut !== CallStatus::BROUILLON->value) {
            return false;
        }

        return $this->update([
            'statut' => CallStatus::OUVERT->value,
            'publie_le' => now(),
        ]);
    }

    /**
     * Ferme l'appel
     */
    public function fermer(): bool
    {
        if (!in_array($this->statut, [CallStatus::OUVERT->value, CallStatus::BROUILLON->value])) {
            return false;
        }

        return $this->update([
            'statut' => CallStatus::FERME->value,
            'ferme_le' => now(),
        ]);
    }

    /**
     * Annule l'appel
     */
    public function annuler(): bool
    {
        if ($this->statut === CallStatus::FERME->value) {
            return false;
        }

        return $this->update([
            'statut' => CallStatus::ANNULE->value,
        ]);
    }

    /**
     * Scope pour les appels ouverts
     */
    public function scopeOuvert(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', CallStatus::OUVERT->value)
                     ->where('date_limite', '>=', now());
    }

    /**
     * Génère un slug unique
     */
    public static function genererSlug(string $titre, ?string $ignoreId = null): string
    {
        $slug = Str::slug($titre, '-');
        $originalSlug = $slug;
        $count = 1;

        while (self::where('slug', $slug)
                   ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                   ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($call) {
            if (empty($call->slug) && !empty($call->titre)) {
                $call->slug = self::genererSlug($call->titre);
            }
        });

        static::updating(function ($call) {
            if ($call->isDirty('titre') && !empty($call->titre) && !$call->isDirty('slug')) {
                $call->slug = self::genererSlug($call->titre, $call->id);
            }
        });
    }
}
