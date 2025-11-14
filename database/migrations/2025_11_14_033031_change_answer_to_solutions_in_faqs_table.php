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
        Schema::table('faqs', function (Blueprint $table) {
            // Add solutions column first
            $table->json('solutions')->nullable()->after('question');
        });
        
        // Convert existing answer data to JSON array format and copy to solutions
        $faqs = \DB::table('faqs')->get();
        foreach ($faqs as $faq) {
            if ($faq->answer) {
                \DB::table('faqs')
                    ->where('id', $faq->id)
                    ->update(['solutions' => json_encode([['solution' => $faq->answer]])]);
            }
        }
        
        Schema::table('faqs', function (Blueprint $table) {
            // Drop the old answer column
            $table->dropColumn('answer');
        });
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
