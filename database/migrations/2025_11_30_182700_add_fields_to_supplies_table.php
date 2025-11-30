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
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name'); // box, mailer, or envelope
            $table->decimal('weight', 8, 2)->nullable()->after('type');
            $table->string('reorder_link')->nullable()->after('weight');
            $table->json('cubic_measurements')->nullable()->after('reorder_link');
            $table->dropColumn('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['type', 'weight', 'reorder_link', 'cubic_measurements']);
            $table->text('description')->nullable();
        });
    }
};
