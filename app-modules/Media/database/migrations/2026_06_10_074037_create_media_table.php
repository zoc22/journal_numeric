<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des médias
     *
     * Stocke tous les fichiers téléchargés sur la plateforme.
     * Supporte le polymorphisme pour l'association à différents modèles.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // === Informations de base ===
            $table->string('nom_fichier', 255);
            $table->string('nom_original', 255);
            $table->string('chemin', 500);
            $table->string('disque', 50)->default('public');

            // === Métadonnées du fichier ===
            $table->string('type_mime', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('taille');
            $table->string('hash', 64)->unique(); // Hash SHA-256 du fichier

            // === Types de médias ===
            $table->enum('type', ['image', 'document', 'video', 'audio', 'other'])->default('other');

            // === Métadonnées additionnelles (JSON) ===
            $table->json('metadonnees')->nullable(); // Dimensions, durée, etc.
            $table->json('variants')->nullable(); // URLs des versions optimisées

            // === Localisation ===
            $table->string('continent')->nullable()->index();
            $table->string('pays')->nullable()->index();
            $table->string('ville')->nullable()->index();

            // === Contexte multi-tenant ===
            $table->uuid('maison_id')->nullable()->index();

            // === Propriétaire ===
            $table->uuid('televerse_par');

            // === Statut ===
            $table->boolean('est_publique')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // === Index optimisés ===
            $table->index(['type', 'created_at']);
            $table->index(['maison_id', 'type']);
            $table->index(['televerse_par']);
            $table->index(['hash']);

            // Contrainte de clé étrangère
            $table->foreign('televerse_par')
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
        Schema::dropIfExists('media');
    }
};
