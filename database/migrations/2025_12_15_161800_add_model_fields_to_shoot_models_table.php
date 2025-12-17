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
            $table->dateTime('submission_date')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('submission_date');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('last_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->text('social_media')->nullable()->after('phone_number');
            $table->string('selfie_url')->nullable()->after('social_media');
            $table->text('coffee_order')->nullable()->after('selfie_url');
            $table->text('food_allergies')->nullable()->after('coffee_order');
            $table->json('tops_size')->nullable()->after('food_allergies');
            $table->json('bottoms_size')->nullable()->after('tops_size');
            $table->text('availability')->nullable()->after('bottoms_size');
            $table->string('height')->nullable()->after('availability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shoot_models', function (Blueprint $table) {
            $table->dropColumn([
                'submission_date',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'social_media',
                'selfie_url',
                'coffee_order',
                'food_allergies',
                'tops_size',
                'bottoms_size',
                'availability',
                'height',
            ]);
        });
    }
};
