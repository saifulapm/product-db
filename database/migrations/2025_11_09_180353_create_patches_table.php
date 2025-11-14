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
        // Check if patches table exists, if not create it, otherwise add missing columns
        if (!Schema::hasTable('patches')) {
            Schema::create('patches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable();
                $table->string('category')->nullable();
                $table->string('size')->nullable();
                $table->string('backing')->nullable();
                $table->text('description')->nullable();
                $table->json('colors')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            // Add size column if it doesn't exist
            Schema::table('patches', function (Blueprint $table) {
                if (!Schema::hasColumn('patches', 'size')) {
                    $table->string('size')->nullable()->after('category');
                }
                // Change colorways to colors if needed
                if (Schema::hasColumn('patches', 'colorways') && !Schema::hasColumn('patches', 'colors')) {
                    $table->json('colors')->nullable()->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table, just remove the size column if it exists
        if (Schema::hasTable('patches') && Schema::hasColumn('patches', 'size')) {
            Schema::table('patches', function (Blueprint $table) {
                $table->dropColumn('size');
            });
        }
    }
};

