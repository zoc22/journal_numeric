<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des tentatives de connexion échouées
     *
     * Utilisée pour le rate limiting et la détection d'attaques par brute force.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();

            $table->string('email', 255)->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            $table->timestamp('attempted_at')->useCurrent();

            // Index pour le nettoyage et les requêtes
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index(['attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};
