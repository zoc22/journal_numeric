<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignUuid('user_id')->constrained();
            $table->string('role_in_tenant')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_user');
    }
};
