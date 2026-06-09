<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des transitions de workflow
     *
     * Enregistre toutes les transitions d'état des articles
     * pour assurer la traçabilité complète du workflow éditorial.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphique pour supporter différents types d'entités
            $table->string('workflowable_type');
            $table->uuid('workflowable_id');

            // Statuts
            $table->string('statut_origine', 50);
            $table->string('statut_cible', 50);

            // Acteur
            $table->uuid('utilisateur_id');
            $table->unsignedSmallInteger('role_niveau')->default(0);
            $table->string('role_utilise', 50)->nullable();

            // Métadonnées
            $table->text('commentaires')->nullable();
            $table->json('metadonnees')->nullable();

            // Contexte
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Localisation
            $table->string('continent', 100)->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('ville', 100)->nullable();

            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['workflowable_type', 'workflowable_id']);
            $table->index(['utilisateur_id']);
            $table->index(['statut_origine', 'statut_cible']);
            $table->index(['created_at']);

            // Contrainte de clé étrangère
            $table->foreign('utilisateur_id')
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
        Schema::dropIfExists('workflow_transitions');
    }
};
