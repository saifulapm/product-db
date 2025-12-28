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
        Schema::create('photoshoots', function (Blueprint $table) {
            $table->id();

            // Basic shoot information
            $table->string('shoot_name');
            $table->string('shoot_type');
            $table->date('date');
            $table->boolean('completed')->default(false);

            // Common fields
            $table->string('mood_board_url')->nullable();

            // Campaign-specific fields
            $table->string('campaign_location')->nullable();
            $table->json('campaign_models')->nullable();
            $table->json('campaign_deliverables_video')->nullable();
            $table->boolean('campaign_deliverables_photo')->default(false);
            $table->string('campaign_deliverables_url')->nullable();

            // Studio Spotlight-specific fields
            $table->string('studio_name')->nullable();
            $table->string('studio_contact_name')->nullable();
            $table->string('studio_phone_number')->nullable();
            $table->string('studio_email')->nullable();
            $table->string('studio_social_media')->nullable();
            $table->text('studio_location')->nullable();
            $table->text('studio_notes')->nullable();
            $table->json('studio_spotlight_deliverables_video')->nullable();
            $table->json('studio_spotlight_deliverables_photo_outside')->nullable();
            $table->json('studio_spotlight_deliverables_photo_inside')->nullable();
            $table->string('studio_spotlight_deliverables_url')->nullable();

            // Team assignment fields (foreign keys to users table)
            $table->foreignId('photographer')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('graphic_designer')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('scout_models')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('model_outreach_communication')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('styling')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_return_props')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('social_media_content')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('concept_deck_call_sheet')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shoot_day_point_of_contact')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shoot_day_assistant')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bts_video_clips')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photoshoots');
    }
};
