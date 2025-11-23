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
        Schema::create('mockups_submissions', function (Blueprint $table) {
            $table->id();
            $table->integer('tracking_number')->unique();
            $table->string('title');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('website')->nullable();
            $table->string('company_name')->nullable();
            $table->string('instagram')->nullable();
            $table->text('notes')->nullable();
            $table->json('products')->nullable();
            $table->json('graphics')->nullable();
            $table->json('pdfs')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('tracking_number');
            $table->index('customer_email');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mockups_submissions');
    }
};
