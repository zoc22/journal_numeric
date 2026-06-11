<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des sessions utilisateur
     *
     * Suit les sessions actives des utilisateurs pour la sécurité
     * et la gestion des connexions simultanées.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('utilisateur_id');
            $table->string('session_id', 255)->unique();
            $table->string('token', 255)->nullable();

            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->nullable();

            // Géolocalisation
            $table->string('continent', 50)->nullable()->index();
            $table->string('pays', 100)->nullable()->index();
            $table->string('ville', 100)->nullable()->index();

            $table->timestamp('last_activity')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('metadonnees')->nullable();

            $table->timestamps();

            // Index optimisés
            $table->index(['utilisateur_id', 'is_active']);
            $table->index(['session_id']);
            $table->index(['last_activity']);
            $table->index(['expires_at']);

            // Contrainte de clé étrangère
            $table->foreign('utilisateur_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
