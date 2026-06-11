<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des appels à candidatures
     *
     * Permet aux maisons d'édition de publier des offres de recrutement
     * pour différents rôles (journaliste, reviewer, éditeur, etc.)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('calls_for_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Informations de base
            $table->string('titre', 255);
            $table->string('slug', 280)->unique();
            $table->text('description');
            $table->json('roles_vises'); // ['journaliste', 'reviewer', ...]

            // Conditions et prérequis
            $table->text('conditions')->nullable();
            $table->json('prerequis')->nullable(); // diplômes, expérience, etc.

            // Dates
            $table->date('date_limite');
            $table->timestamp('publie_le')->nullable();
            $table->timestamp('ferme_le')->nullable();

            // Localisation
            $table->string('continent')->nullable()->index();
            $table->string('pays')->nullable()->index();
            $table->string('ville')->nullable()->index();

            // Statut
            $table->string('statut', 50)->default('brouillon');

            // Métadonnées
            $table->integer('nombre_postes')->default(1);
            $table->json('metadonnees')->nullable();

            // Contexte multi-tenant
            $table->string('maison_id')->index();

            // Créateur
            $table->uuid('cree_par');

            $table->timestamps();
            $table->softDeletes();

            // Index optimisés
            $table->index(['statut', 'date_limite']);
            $table->index(['maison_id', 'statut']);
            $table->index(['cree_par']);
            $table->index(['slug']);

            // Contraintes de clé étrangère (commentées pour compatibilité SQLite en test multi-tenant)
            /*
            $table->foreign('maison_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');

            $table->foreign('cree_par')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls_for_applications');
    }
};
