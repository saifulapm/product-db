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
            $table->string('image_reference')->nullable()->after('description');
            $table->string('minimums')->nullable()->after('image_reference');
            $table->string('lead_time')->nullable()->after('minimums');
            $table->string('pricing')->nullable()->after('lead_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patches', function (Blueprint $table) {
            $table->dropColumn(['image_reference', 'minimums', 'lead_time', 'pricing']);
        });
    }
};

