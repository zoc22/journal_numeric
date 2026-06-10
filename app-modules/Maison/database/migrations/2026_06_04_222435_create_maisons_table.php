<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour créer la table des maisons d'édition.
 * Cette table est stockée dans la base de données du TENANT.
 * Chaque maison d'édition est un tenant distinct.
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::create('maisons', function (Blueprint $table) {
            // Clé primaire : UUID généré automatiquement
            $table->uuid('id')->primary();

            // Informations de base de la maison d'édition
            $table->string('nom', 255)->unique();
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();

            // Localisation
            $table->string('continent')->nullable()->index();
            $table->string('pays')->nullable()->index();
            $table->string('ville')->nullable()->index();

            // Contact principal
            $table->string('email_contact', 255);

            // Gestion du statut de la maison
            $table->enum('statut', [
                'en_attente',  // En attente de validation par l'admin plateforme
                'active',      // Maison validée et active
                'suspendue',   // Maison temporairement suspendue
                'rejetee',     // Demande de création rejetée
            ])->default('en_attente');

            // Validation par l'admin plateforme (référence vers un utilisateur central)
            $table->uuid('validee_par')->nullable();
            $table->timestamp('validee_le')->nullable();

            // Gestion des dates (création, modification)
            $table->timestamps();

            // Index pour accélérer les recherches
            $table->index('slug');
            $table->index('statut');
            $table->index('email_contact');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('maisons');
    }
};
