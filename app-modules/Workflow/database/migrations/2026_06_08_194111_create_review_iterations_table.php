<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des itérations de review
     *
     * Suit le cycle complet des allers-retours entre journaliste et reviewers.
     * Chaque itération représente un cycle correction → re-soumission → re-review.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_iterations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('article_id');
            $table->uuid('journaliste_id');

            // Numéro de l'itération
            $table->unsignedSmallInteger('numero_iteration')->default(1);

            // Statut de l'itération
            $table->enum('statut', [
                'correction_demandee',
                'correction_soumise',
                'en_review',
                'terminee',
            ])->default('correction_demandee');

            // Contenu de l'itération
            $table->text('corrections_apportees')->nullable();
            $table->text('notes_journaliste')->nullable();

            // Dates clés
            $table->timestamp('correction_demandee_le')->nullable();
            $table->timestamp('correction_soumise_le')->nullable();
            $table->timestamp('terminee_le')->nullable();

            $table->timestamps();

            // Index
            $table->index(['article_id']);
            $table->index(['article_id', 'numero_iteration']);
            $table->unique(['article_id', 'numero_iteration']);
            $table->index(['journaliste_id']);
            $table->index(['statut']);

            // Contraintes de clé étrangère
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('journaliste_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_iterations');
    }
};
