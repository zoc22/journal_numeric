<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des versions d'articles
     *
     * Permet le versioning complet des articles avec traçabilité des modifications.
     * Chaque version conserve un snapshot complet du contenu à un moment T.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('article_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Référence à l'article parent
            $table->uuid('article_id');

            // Numéro de version (incrémenté automatiquement)
            $table->unsignedSmallInteger('numero_version');

            // Snapshot du contenu à cette version
            $table->string('titre', 500);
            $table->longText('contenu');
            $table->text('resume')->nullable();
            $table->string('image_principale')->nullable();
            $table->json('galerie_medias')->nullable();

            // Snapshot des métadonnées SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // Auteur de cette version
            $table->uuid('cree_par');

            // Métadonnées de la version
            $table->text('resume_modifications')->nullable();
            $table->json('changements_detailles')->nullable();

            // Indicateurs de version
            $table->boolean('est_publiee')->default(false);
            $table->boolean('est_actuelle')->default(false);

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->unique(['article_id', 'numero_version']);
            $table->index(['article_id', 'created_at']);
            $table->index(['cree_par', 'created_at']);
            $table->index(['article_id', 'est_actuelle']);

            // Contraintes de clé étrangère
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_versions');
    }
};
