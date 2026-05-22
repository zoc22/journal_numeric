<?php

namespace App\Models;

use Stancl\Tenancy\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'domain',
        'tenant_id',
        'is_primary',
        'verification_status',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Scope pour récupérer les domaines primaires.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Vérifie si le domaine est vérifié.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }
}