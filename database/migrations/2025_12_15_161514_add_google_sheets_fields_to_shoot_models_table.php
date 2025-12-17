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
        Schema::table('shoot_models', function (Blueprint $table) {
            $table->timestamp('google_sheets_timestamp')->nullable()->after('name');
            $table->string('google_sheets_row_id')->nullable()->after('google_sheets_timestamp');
            $table->json('google_sheets_data')->nullable()->after('google_sheets_row_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shoot_models', function (Blueprint $table) {
            $table->dropColumn(['google_sheets_timestamp', 'google_sheets_row_id', 'google_sheets_data']);
        });
    }
};
