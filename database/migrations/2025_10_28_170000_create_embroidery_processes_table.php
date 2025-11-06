<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embroidery_processes', function (Blueprint $table) {
            $table->id();
            $table->integer('step_number')->default(1);
            $table->string('step_title')->nullable();
            $table->text('description')->nullable();
            $table->string('equipment_required')->nullable();
            $table->string('materials_needed')->nullable();
            $table->string('estimated_time')->nullable();
            $table->text('special_notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embroidery_processes');
    }
};




