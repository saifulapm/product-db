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
        Schema::table('task_comments', function (Blueprint $table) {
            $table->foreignId('parent_comment_id')->nullable()->after('task_id')->constrained('task_comments')->nullOnDelete();
            $table->json('reactions')->nullable()->after('tagged_users')->comment('JSON object with reaction types and user IDs, e.g., {"thumbs_up": [1, 2], "heart": [3]}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_comment_id']);
            $table->dropColumn(['parent_comment_id', 'reactions']);
        });
    }
};
