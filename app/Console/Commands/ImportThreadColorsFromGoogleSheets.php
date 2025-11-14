<?php

namespace App\Console\Commands;

use App\Models\ThreadColor;
use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;

class ImportThreadColorsFromGoogleSheets extends Command
{
    protected $signature = 'thread-colors:import-from-sheets {spreadsheet_id} {--range=Madeira Swatches!A:B}';
    protected $description = 'Import thread colors from Google Sheets with their image URLs';

    public function handle()
    {
        $spreadsheetId = $this->argument('spreadsheet_id');
        $range = $this->option('range');

        $this->info('Starting thread colors import from Google Sheets...');
        $this->info("Spreadsheet ID: {$spreadsheetId}");
        $this->info("Range: {$range}");

        try {
            $googleSheetsService = new GoogleSheetsService();
            $threadColors = $googleSheetsService->getThreadColorsFromSheet($spreadsheetId, $range);

            if (empty($threadColors)) {
                $this->error('No thread colors found in the specified range.');
                return 1;
            }

            $this->info('Found ' . count($threadColors) . ' thread colors');

            // Clear existing data
            ThreadColor::truncate();
            $this->info('Cleared existing thread colors');

            // Import new data
            $imported = 0;
            foreach ($threadColors as $threadColor) {
                ThreadColor::create([
                    'color_name' => $threadColor['color_name'],
                    'color_code' => $threadColor['color_code'],
                    'image_url' => $threadColor['image_url'] ?? 'https://via.placeholder.com/120x59?text=' . $threadColor['color_name'],
                ]);
                $imported++;
            }

            $this->info("Successfully imported {$imported} thread colors");
            return 0;

        } catch (\Exception $e) {
            $this->error('Error importing thread colors: ' . $e->getMessage());
            return 1;
        }
    }
}










