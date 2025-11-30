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
        Schema::table('garments', function (Blueprint $table) {
            $table->json('cubic_dimensions')->nullable()->after('measurements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garments', function (Blueprint $table) {
            $table->dropColumn('cubic_dimensions');
        });
    }
};
