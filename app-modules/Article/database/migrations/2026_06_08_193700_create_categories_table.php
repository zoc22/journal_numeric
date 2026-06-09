<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des catégories
     *
     * Structure arborescente pour organiser les articles par thèmes,
     * rubriques ou collections. Supporte une hiérarchie infinie.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Informations de base
            $table->string('nom', 255);
            $table->string('slug', 280)->unique();
            $table->text('description')->nullable();

            // Hiérarchie
            $table->uuid('parent_id')->nullable()->index();
            $table->string('chemin', 500)->nullable();
            $table->unsignedSmallInteger('niveau')->default(0);

            // Métadonnées visuelles
            $table->string('icone')->nullable();
            $table->string('couleur', 7)->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            // Organisation
            $table->integer('ordre')->default(0);

            // Multi-tenant
            $table->uuid('maison_id')->nullable()->index();

            // Statut
            $table->boolean('est_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Index optimisés
            $table->index(['parent_id', 'ordre']);
            $table->index(['chemin']);
            $table->index(['maison_id', 'est_active']);
            $table->index(['slug', 'maison_id']);

            /* // Contrainte d'auto-référencement
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null'); */
        });

        // Contrainte d'auto-référencement (déplacée après la création de la table)
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
