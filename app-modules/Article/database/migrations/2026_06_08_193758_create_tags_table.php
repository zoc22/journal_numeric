<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des tags
     *
     * Tags pour le marquage sémantique des articles.
     * Utilisés pour les recherches, les recommandations et le SEO.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nom', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();

            $table->uuid('maison_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['nom']);
            $table->index(['maison_id']);
            $table->unique(['slug', 'maison_id']);
        });

        // Table pivot article-tag
        Schema::create('article_tag', function (Blueprint $table) {
            $table->uuid('article_id');
            $table->uuid('tag_id');

            $table->timestamps();

            $table->unique(['article_id', 'tag_id']);
            $table->index(['article_id']);
            $table->index(['tag_id']);

            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tags')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('tags');
    }
};
