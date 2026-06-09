<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot article-catégorie
     *
     * Relation many-to-many entre articles et catégories.
     * Un article peut appartenir à plusieurs catégories.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table) {
            $table->uuid('article_id');
            $table->uuid('categorie_id');

            $table->timestamps();

            // Index uniques pour éviter les doublons
            $table->unique(['article_id', 'categorie_id']);

            // Index pour les requêtes
            $table->index(['article_id']);
            $table->index(['categorie_id']);

            // Contraintes de clé étrangère
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('categorie_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
