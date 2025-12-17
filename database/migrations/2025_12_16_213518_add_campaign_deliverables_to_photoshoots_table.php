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
        Schema::table('photoshoots', function (Blueprint $table) {
            $table->json('campaign_deliverables_video')->nullable()->after('campaign_models');
            $table->boolean('campaign_deliverables_photo')->default(false)->after('campaign_deliverables_video');
            $table->string('campaign_deliverables_url')->nullable()->after('campaign_deliverables_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photoshoots', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_deliverables_video',
                'campaign_deliverables_photo',
                'campaign_deliverables_url',
            ]);
        });
    }
};
