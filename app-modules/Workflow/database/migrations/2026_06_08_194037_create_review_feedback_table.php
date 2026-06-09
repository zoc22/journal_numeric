<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des feedbacks de review
     *
     * Enregistre les commentaires, décisions et annotations des reviewers.
     * Supporte plusieurs itérations de corrections.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('review_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('review_assignment_id');
            $table->uuid('article_id');
            $table->uuid('reviewer_id');

            // Itération
            $table->unsignedSmallInteger('numero_iteration')->default(1);

            // Contenu du feedback
            $table->text('commentaire_global')->nullable();
            $table->json('annotations')->nullable(); // Annotations sur des positions spécifiques
            $table->json('corrections_demandees')->nullable(); // Liste structurée des corrections

            // Décision
            $table->enum('decision', [
                'correction',
                'validation',
                'rejet',
            ]);

            // Score de qualité (optionnel)
            $table->unsignedTinyInteger('score_qualite')->nullable()->comment('1-5');

            // Dates
            $table->timestamp('retour_le')->useCurrent();

            $table->timestamps();

            // Index
            $table->index(['review_assignment_id']);
            $table->index(['article_id']);
            $table->index(['reviewer_id']);
            $table->index(['decision']);
            $table->index(['article_id', 'numero_iteration']);

            // Contraintes de clé étrangère
            $table->foreign('review_assignment_id')
                  ->references('id')
                  ->on('review_assignments')
                  ->onDelete('cascade');

            $table->foreign('article_id')
                  ->references('id')
                  ->on('articles')
                  ->onDelete('cascade');

            $table->foreign('reviewer_id')
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
        Schema::dropIfExists('review_feedback');
    }
};
