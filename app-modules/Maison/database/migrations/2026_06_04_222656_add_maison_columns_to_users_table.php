<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour ajouter les champs de localisation et informations
 * complémentaires à la table users.
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Informations personnelles complémentaires
            if (!Schema::hasColumn('users', 'prenom')) {
                $table->string('prenom', 255)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone', 50)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'biographie')) {
                $table->string('biographie', 1000)->nullable();
            }

            // Localisation géographique
            if (!Schema::hasColumn('users', 'continent')) {
                $table->string('continent', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'pays')) {
                $table->string('pays', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'ville')) {
                $table->string('ville', 100)->nullable();
            }

            // Compte utilisateur
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }

            // Gestion des soft deletes
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'prenom',
                'telephone',
                'biographie',
                'continent',
                'pays',
                'ville',
                'is_active',
                'email_verified_at',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
