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
        Schema::table('mockups_submission_comments', function (Blueprint $table) {
            $table->json('mentioned_users')->nullable()->after('message')->comment('Array of user IDs mentioned in this comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mockups_submission_comments', function (Blueprint $table) {
            $table->dropColumn('mentioned_users');
        });
    }
};
