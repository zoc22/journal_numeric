<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // === Colonnes ajoutées pour le métier ===
            $table->string('name')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->string('logo')->nullable();
            $table->string('plan')->default('basic');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->json('settings')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users');
            // =====================================

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
