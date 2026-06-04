<?php
// app-modules/User/Database/Migrations/2024_01_01_000004_create_login_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des connexions des utilisateurs.
     * Permet le suivi de sécurité et l'audit.
     */
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable(); // Pays/Ville (via GeoIP)
            $table->boolean('success')->default(true);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
