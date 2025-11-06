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
        Schema::table('reference_images', function (Blueprint $table) {
            $table->dropColumn(['title', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reference_images', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
        });
    }
};
