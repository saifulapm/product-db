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
        Schema::create('shared_table_views', function (Blueprint $table) {
            $table->id();
            $table->string('resource_name'); // e.g., 'TaskResource'
            $table->string('view_name')->nullable(); // Optional name for the view
            $table->json('filters')->nullable(); // Active filters
            $table->string('sort_column')->nullable(); // Column to sort by
            $table->string('sort_direction')->nullable(); // 'asc' or 'desc'
            $table->json('column_visibility')->nullable(); // Which columns are visible
            $table->string('search_query')->nullable(); // Search query
            $table->boolean('is_default')->default(false); // Is this the default view?
            $table->integer('created_by')->nullable(); // User who created it
            $table->timestamps();
            
            $table->index('resource_name');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_table_views');
    }
};
