<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des assignations de reviewers
     *
     * Enregistre quels reviewers sont assignés à quels articles.
     * Supporte plusieurs reviewers par article et un ordre de review.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('article_id');
            $table->uuid('reviewer_id');
            $table->uuid('assigne_par');

            // Ordre de review (1, 2, 3...)
            $table->unsignedSmallInteger('ordre_review')->default(1);

            // Statut de l'assignation
            $table->enum('statut', [
                'en_attente',
                'accepte',
                'refuse',
                'en_cours',
                'retour_envoye',
                'termine',
                'expire',
            ])->default('en_attente');

            // Dates importantes
            $table->timestamp('assigne_le')->useCurrent();
            $table->timestamp('accepte_le')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('termine_le')->nullable();

            // Métadonnées
            $table->text('note_interne')->nullable();
            $table->json('metadonnees')->nullable();

            $table->timestamps();

            // Index pour les requêtes
            $table->index(['article_id', 'statut']);
            $table->index(['reviewer_id', 'statut']);
            $table->index(['assigne_par']);
            $table->index(['deadline']);
            $table->unique(['article_id', 'reviewer_id']);

            // Contraintes de clé étrangère
            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('reviewer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('assigne_par')
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
        Schema::dropIfExists('review_assignments');
    }
};
