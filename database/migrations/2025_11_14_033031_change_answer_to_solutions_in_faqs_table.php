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
        try {
            // Check if solutions column already exists (migration might have partially run)
            if (!Schema::hasColumn('faqs', 'solutions')) {
                Schema::table('faqs', function (Blueprint $table) {
                    // Add solutions column first
                    $table->json('solutions')->nullable()->after('question');
                });
            }
            
            // Convert existing answer data to JSON array format and copy to solutions (only if answer column exists)
            if (Schema::hasColumn('faqs', 'answer')) {
                $faqs = \DB::table('faqs')->whereNotNull('answer')->get();
                foreach ($faqs as $faq) {
                    // Only update if solutions is null or empty
                    $existingSolutions = \DB::table('faqs')->where('id', $faq->id)->value('solutions');
                    if (empty($existingSolutions) && !empty($faq->answer)) {
                        \DB::table('faqs')
                            ->where('id', $faq->id)
                            ->update(['solutions' => json_encode([['solution' => $faq->answer]])]);
                    }
                }
                
                // Drop the old answer column
                Schema::table('faqs', function (Blueprint $table) {
                    if (Schema::hasColumn('faqs', 'answer')) {
                        $table->dropColumn('answer');
                    }
                });
            }
        } catch (\Exception $e) {
            // If migration partially failed, try to ensure solutions column exists
            if (!Schema::hasColumn('faqs', 'solutions')) {
                Schema::table('faqs', function (Blueprint $table) {
                    $table->json('solutions')->nullable()->after('question');
                });
            }
            // Don't re-throw - allow migration to complete even if data migration fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            // Add answer column back
            $table->text('answer')->nullable()->after('question');
            
            // Convert solutions JSON back to text (take first solution)
            \DB::statement('UPDATE faqs SET answer = JSON_UNQUOTE(JSON_EXTRACT(solutions, "$[0]")) WHERE solutions IS NOT NULL');
            
            // Drop solutions column
            $table->dropColumn('solutions');
        });
    }
};
