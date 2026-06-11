<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des historiques de connexion
     *
     * Enregistre chaque tentative de connexion (succès ou échec)
     * pour le suivi de sécurité et l'analyse des comportements suspects.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Utilisateur concerné
            $table->uuid('utilisateur_id')->nullable();
            $table->string('email', 255);

            // Résultat de la tentative
            $table->boolean('succes');
            $table->string('message_erreur')->nullable();

            // Contexte de connexion
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();

            // Géolocalisation (optionnelle)
            $table->string('country_code', 2)->nullable();
            $table->string('continent', 50)->nullable()->index();
            $table->string('pays', 100)->nullable()->index();
            $table->string('ville', 100)->nullable()->index();
            $table->string('city', 100)->nullable();

            // Métadonnées
            $table->json('metadonnees')->nullable();

            $table->timestamps();

            // Index optimisés
            $table->index(['utilisateur_id', 'created_at']);
            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['succes', 'created_at']);

            // Contrainte de clé étrangère
            $table->foreign('utilisateur_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
