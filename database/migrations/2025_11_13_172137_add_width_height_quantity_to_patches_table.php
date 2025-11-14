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
        Schema::table('patches', function (Blueprint $table) {
            $table->decimal('width', 8, 2)->nullable()->after('size');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->integer('quantity')->default(10)->after('minimums');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patches', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'quantity']);
        });
    }
};
