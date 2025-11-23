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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('mockups_customer_name')->nullable()->after('website_images_attachments');
            $table->string('mockups_customer_email')->nullable()->after('mockups_customer_name');
            $table->string('mockups_customer_phone')->nullable()->after('mockups_customer_email');
            $table->string('mockups_website')->nullable()->after('mockups_customer_phone');
            $table->string('mockups_company_name')->nullable()->after('mockups_website');
            $table->string('mockups_instagram')->nullable()->after('mockups_company_name');
            $table->text('mockups_notes')->nullable()->after('mockups_instagram');
            $table->json('mockups_products')->nullable()->after('mockups_notes');
            $table->json('mockups_graphics')->nullable()->after('mockups_products');
            $table->json('mockups_pdfs')->nullable()->after('mockups_graphics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'mockups_customer_name',
                'mockups_customer_email',
                'mockups_customer_phone',
                'mockups_website',
                'mockups_company_name',
                'mockups_instagram',
                'mockups_notes',
                'mockups_products',
                'mockups_graphics',
                'mockups_pdfs',
            ]);
        });
    }
};
