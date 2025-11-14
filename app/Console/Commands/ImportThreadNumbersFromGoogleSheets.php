<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleSheetsService;
use App\Models\ThreadColor;

class ImportThreadNumbersFromGoogleSheets extends Command
{
    protected $signature = 'thread-colors:import-numbers {--limit=100 : Number of thread colors to import}';
    protected $description = 'Import thread color numbers from Google Sheets column A';

    public function handle()
    {
        $this->info('Starting thread color numbers import from Google Sheets...');
        
        try {
            $limit = $this->option('limit');
            $this->info("Processing {$limit} thread colors...");
            
            $googleSheetsService = new GoogleSheetsService();
            
            // Get thread colors from Google Sheets
            $spreadsheetId = '1gTHgdksxGx7CThTbAENPJ44ndhCJBJPoEn0l1_68QK8';
            $threadColors = $googleSheetsService->getThreadColorsFromSheet($spreadsheetId, 'Madeira Swatches!A:B');
            
            if (empty($threadColors)) {
                $this->error('No thread colors found in Google Sheets');
                return 1;
            }
            
            // Limit processing
            $threadColors = array_slice($threadColors, 0, $limit);
            $this->info("Found " . count($threadColors) . " thread colors to process");
            
            $imported = 0;
            $updated = 0;
            $created = 0;
            
            foreach ($threadColors as $index => $threadColor) {
                try {
                    $threadNumber = $threadColor['color_code'];
                    $this->info("Processing thread color {$threadNumber} (" . ($index + 1) . "/{$limit})");
                    
                    // Check if thread color already exists
                    $existingThread = ThreadColor::where('color_code', $threadNumber)->first();
                    
                    if ($existingThread) {
                        // Update existing thread color
                        $existingThread->update([
                            'color_name' => $threadNumber,
                            'color_code' => $threadNumber,
                        ]);
                        $updated++;
                        $this->info("  ✓ Updated existing thread color {$threadNumber}");
                    } else {
                        // Create new thread color
                        ThreadColor::create([
                            'color_name' => $threadNumber,
                            'color_code' => $threadNumber,
                            'image_url' => null, // No image URL yet
                        ]);
                        $created++;
                        $this->info("  ✓ Created new thread color {$threadNumber}");
                    }
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $this->error("  ✗ Error with thread color {$threadNumber}: " . $e->getMessage());
                }
            }
            
            $this->info('');
            $this->info("✅ Import completed successfully!");
            $this->info("📊 Processed: {$imported} thread colors");
            $this->info("🆕 Created: {$created} new thread colors");
            $this->info("🔄 Updated: {$updated} existing thread colors");
            $this->info("🖼️ Image URLs: Ready for manual linking");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}









