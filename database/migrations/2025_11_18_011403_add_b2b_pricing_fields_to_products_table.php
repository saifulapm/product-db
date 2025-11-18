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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('b2b_price', 10, 2)->nullable()->after('price');
            $table->decimal('printed_embroidered_1_logo', 10, 2)->nullable()->after('b2b_price');
            $table->decimal('printed_embroidered_2_logos', 10, 2)->nullable()->after('printed_embroidered_1_logo');
            $table->decimal('printed_embroidered_3_logos', 10, 2)->nullable()->after('printed_embroidered_2_logos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'b2b_price',
                'printed_embroidered_1_logo',
                'printed_embroidered_2_logos',
                'printed_embroidered_3_logos',
            ]);
        });
    }
};
