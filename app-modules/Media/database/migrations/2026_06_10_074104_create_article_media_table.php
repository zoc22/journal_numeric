<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot article-media
     *
     * Lie les médias aux articles avec des informations contextuelles.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('article_media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('article_id');
            $table->uuid('media_id');

            // Type d'utilisation du média dans l'article
            $table->enum('type_usage', [
                'couverture',      // Image à la une
                'inline',          // Intégré dans le contenu
                'galerie',         // Partie d'une galerie
                'piece_jointe',    // Document attaché
                'podcast',         // Podcast audio
                'video'            // Vidéo intégrée
            ])->default('inline');

            // Métadonnées spécifiques à l'article
            $table->json('metadonnees')->nullable(); // Légende, crédits, position, etc.
            $table->unsignedSmallInteger('ordre_affichage')->default(0);
            $table->boolean('est_actif')->default(true);

            $table->timestamps();

            // Index
            $table->unique(['article_id', 'media_id']);
            $table->index(['article_id', 'type_usage']);
            $table->index(['media_id']);
            $table->index(['article_id', 'ordre_affichage']);

            // Contraintes de clé étrangère
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('media_id')
                  ->references('id')
                  ->on('media')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_media');
    }
};
