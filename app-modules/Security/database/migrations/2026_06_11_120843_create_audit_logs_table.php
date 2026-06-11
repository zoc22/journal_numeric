<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des logs d'audit
     *
     * Enregistre toutes les actions sensibles effectuées sur la plateforme.
     * Supporte le multi-tenant et le polymorphisme.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Contexte multi-tenant
            $table->string('maison_id')->nullable();
            $table->string('contexte', 50); // platform, tenant

            // Entité concernée (polymorphique)
            $table->string('entite_type')->nullable();
            $table->uuid('entite_id')->nullable();

            // Action effectuée
            $table->string('action', 100);
            $table->string('module', 50);

            // Acteur
            $table->uuid('utilisateur_id');
            $table->string('utilisateur_nom')->nullable();
            $table->string('utilisateur_role')->nullable();

            // Changements
            $table->json('anciennes_valeurs')->nullable();
            $table->json('nouvelles_valeurs')->nullable();
            $table->json('champs_modifies')->nullable();

            // Métadonnées
            $table->json('metadonnees')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            // Géolocalisation (optionnelle)
            $table->string('continent', 50)->nullable()->index();
            $table->string('pays', 100)->nullable()->index();
            $table->string('ville', 100)->nullable()->index();

            // Niveau de criticité (info, warning, critical)
            $table->enum('niveau', ['info', 'warning', 'critical'])->default('info');

            $table->timestamps();

            // Index optimisés
            $table->index(['maison_id', 'created_at']);
            $table->index(['utilisateur_id', 'created_at']);
            $table->index(['entite_type', 'entite_id']);
            $table->index(['action', 'module']);
            $table->index(['niveau', 'created_at']);
            $table->index(['created_at']);

            // Contraintes de clé étrangère
            $table->foreign('utilisateur_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            $table->foreign('maison_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
