<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'continent')) {
                $table->string('continent')->nullable()->after('telephone');
            }
            if (!Schema::hasColumn('users', 'pays')) {
                $table->string('pays')->nullable()->after('continent');
            }
            if (!Schema::hasColumn('users', 'ville')) {
                $table->string('ville')->nullable()->after('pays');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['continent', 'pays', 'ville']);
        });
    }
};
