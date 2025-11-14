<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dst_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('design_name')->nullable();
            $table->text('description')->nullable();
            $table->string('file_type')->nullable(); // DST, EXP, PES, etc.
            $table->integer('stitch_count')->nullable();
            $table->string('thread_colors_needed')->nullable();
            $table->string('size_dimensions')->nullable();
            $table->text('usage_instructions')->nullable();
            $table->text('application_notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dst_files');
    }
};










