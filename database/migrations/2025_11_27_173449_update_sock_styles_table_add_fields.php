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
        Schema::table('sock_styles', function (Blueprint $table) {
            $table->dropColumn(['description', 'image_url']);
            $table->string('style')->nullable()->after('name');
            $table->string('color')->nullable()->after('style');
            $table->string('packaging_style')->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sock_styles', function (Blueprint $table) {
            $table->dropColumn(['style', 'color', 'packaging_style']);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
        });
    }
};
