<?php

namespace App\Models;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'logo',
        'plan',
        'status',
        'expires_at',
        'settings',
        'created_by',
        'data', // champ JSON par défaut du package
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'settings' => 'array',
        'data' => 'array',
    ];

    /**
     * Relation avec l'utilisateur qui a créé la maison (admin plateforme).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Récupère les utilisateurs de ce tenant (via la connexion dynamique).
     * Note : cette relation est utile dans la base centrale uniquement si
     * vous avez une table 'tenant_users' ou similaire. Sinon, utilisez
     * les requêtes dans le contexte tenant.
     */
    public function users()
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Vérifie si le tenant est actif.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Active le tenant.
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Suspend le tenant.
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }
}