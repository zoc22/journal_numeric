<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des articles
     *
     * Cette table stocke tous les articles de la plateforme avec leurs métadonnées.
     * Le système supporte un workflow éditorial complet avec plusieurs statuts.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            // Identifiant UUID (support multi-tenant)
            $table->uuid('id')->primary();

            // === Champs principaux ===
            $table->string('titre', 500);
            $table->string('slug', 550)->unique();
            $table->longText('contenu');
            $table->text('resume')->nullable();

            // === Médias ===
            $table->string('image_principale')->nullable();
            $table->json('galerie_medias')->nullable();

            // === SEO ===
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // === Métriques d'engagement ===
            $table->unsignedInteger('nb_vues')->default(0);
            $table->unsignedInteger('nb_partages')->default(0);
            $table->unsignedInteger('nb_likes')->default(0);
            $table->unsignedInteger('nb_commentaires')->default(0);

            // === Temps de lecture estimé ===
            $table->unsignedSmallInteger('temps_lecture')->nullable();

            // === Localisation ===
            $table->string('continent', 100)->nullable()->index();
            $table->string('pays', 100)->nullable()->index();
            $table->string('ville', 100)->nullable()->index();

            // === Clés étrangères ===
            $table->uuid('maison_id')->nullable()->index();
            $table->uuid('auteur_id')->index();
            $table->uuid('version_actuelle_id')->nullable();

            // === Workflow ===
            $table->string('statut', 50)->default('brouillon')->index();
            $table->unsignedSmallInteger('version_numero')->default(1);

            // === Dates importantes ===
            $table->timestamp('publie_le')->nullable();
            $table->timestamp('archive_le')->nullable();

            // === Timestamps standards ===
            $table->timestamps();
            $table->softDeletes();

            // === Index optimisés pour les requêtes courantes ===
            $table->index(['statut', 'created_at']);
            $table->index(['maison_id', 'statut']);
            $table->index(['auteur_id', 'statut']);
            $table->index(['publie_le', 'statut']);

            // === Index full-text pour la recherche ===
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['titre', 'contenu', 'resume']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
