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
        Schema::create('product_pricing_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('product_name')->nullable();
            $table->decimal('product_cost', 10, 2);
            $table->decimal('primary_width', 10, 2);
            $table->decimal('primary_height', 10, 2);
            $table->boolean('has_second_logo')->default(false);
            $table->decimal('secondary_width', 10, 2)->nullable();
            $table->decimal('secondary_height', 10, 2)->nullable();
            $table->decimal('total_graphic_area', 12, 2);
            $table->decimal('graphic_cost', 10, 2);
            $table->decimal('final_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_pricing_calculations');
    }
};


