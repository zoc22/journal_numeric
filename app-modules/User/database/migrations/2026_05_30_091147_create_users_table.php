<?php
// app-modules/User/Database/Migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des utilisateurs (dans chaque base tenant).
     * Les utilisateurs sont propres à chaque maison d'édition.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('nom');
                $table->string('prenom')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->string('telephone')->nullable();
                $table->string('continent')->nullable();
                $table->string('pays')->nullable();
                $table->string('ville')->nullable();
                $table->string('poste')->nullable(); // Fonction dans la maison
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes(); // Suppression douce

                // Index pour les recherches fréquentes
                $table->index('email');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
