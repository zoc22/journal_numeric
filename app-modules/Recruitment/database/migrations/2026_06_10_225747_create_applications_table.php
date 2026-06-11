<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des candidatures
     *
     * Enregistre les candidatures soumises par les candidats
     * en réponse aux appels à candidatures.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('appel_id');
            $table->uuid('candidat_id');

            // Contenu de la candidature
            $table->text('lettre_motivation');
            $table->json('experiences')->nullable(); // Expériences professionnelles
            $table->json('formations')->nullable(); // Formations et diplômes
            $table->json('portfolio')->nullable(); // Liens vers travaux
            $table->json('competences')->nullable(); // Compétences listées

            // Documents (CV, lettres, etc.)
            $table->json('documents')->nullable(); // URLs des fichiers
            $table->string('cv_url')->nullable();

            // Localisation du candidat
            $table->string('continent')->nullable()->index();
            $table->string('pays')->nullable()->index();
            $table->string('ville')->nullable()->index();

            // Statut et suivi
            $table->string('statut', 50)->default('en_attente');
            $table->uuid('examinee_par')->nullable();
            $table->text('commentaires_examen')->nullable();
            $table->integer('score')->nullable(); // Score de 0 à 100

            // Dates importantes
            $table->timestamp('soumise_le')->useCurrent();
            $table->timestamp('examinee_le')->nullable();

            $table->timestamps();

            // Index optimisés
            $table->index(['appel_id', 'statut']);
            $table->index(['candidat_id']);
            $table->index(['statut', 'soumise_le']);
            $table->index(['examinee_par']);
            $table->unique(['appel_id', 'candidat_id']);

            // Contraintes de clé étrangère (commentées pour compatibilité SQLite en test multi-tenant)
            /*
            $table->foreign('appel_id')
                  ->references('id')
                  ->on('calls_for_applications')
                  ->onDelete('cascade');

            $table->foreign('candidat_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('examinee_par')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
