<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour créer la table pivot entre les utilisateurs et les maisons.
 * Cette table gère l'appartenance des utilisateurs à une maison d'édition
 * ainsi que leur rôle au sein de cette maison.
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::create('membre_maison', function (Blueprint $table) {
            // Clé primaire : UUID généré automatiquement
            $table->uuid('id')->primary();

            // Clés étrangères
            $table->uuid('maison_id');
            $table->uuid('utilisateur_id');
            $table->unsignedBigInteger('role_id')->nullable();

            // Statut du membre dans la maison
            $table->boolean('est_actif')->default(true);

            // Dates d'adhésion et de départ
            $table->timestamp('a_rejoint_le')->useCurrent();
            $table->timestamp('a_quitte_le')->nullable();

            // Gestion des dates (création, modification)
            $table->timestamps();

            // Index pour accélérer les recherches
            $table->index('maison_id');
            $table->index('utilisateur_id');
            $table->index('role_id');
            $table->index('est_actif');

            // Index composite pour les recherches fréquentes
            $table->index(['maison_id', 'utilisateur_id']);

            // Clés étrangères (attention : les tables référencées existent dans le tenant)
            $table->foreign('maison_id')->references('id')->on('maisons')->onDelete('cascade');
            $table->foreign('utilisateur_id')->references('id')->on('users')->onDelete('cascade');
            // La table roles est gérée par spatie/laravel-permission
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('membre_maison');
    }
};
