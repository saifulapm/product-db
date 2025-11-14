<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DropboxService;
use App\Models\ThreadColor;

class ImportThreadColorsFromDropbox extends Command
{
    protected $signature = 'thread-colors:import-from-dropbox {--limit=20 : Number of thread colors to import}';
    protected $description = 'Import thread colors from Dropbox folder';

    public function handle()
    {
        $this->info('Starting thread colors import from Dropbox...');
        
        try {
            $limit = $this->option('limit');
            $this->info("Processing {$limit} thread colors from Dropbox...");
            
            $dropboxService = new DropboxService();
            
            // Your Dropbox folder URL
            $folderUrl = 'https://www.dropbox.com/scl/fo/fj3phxbxxvfn6qfx3c2lg/AHTEtHjGdhXaJYwL6kyPsdc?rlkey=yghikl21cpob1h5u7le9i7900&st=ch8s48n5&dl=0';
            
            $this->info('Fetching files from Dropbox folder...');
            $files = $dropboxService->getSharedFolderContents($folderUrl);
            
            if (empty($files)) {
                $this->error('No files found in Dropbox folder');
                return 1;
            }
            
            // Filter for image files and limit
            $imageFiles = array_filter($files, function($file) {
                if ($file['.tag'] !== 'file') return false;
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            });
            
            $imageFiles = array_slice($imageFiles, 0, $limit);
            $this->info("Found " . count($imageFiles) . " image files to process");
            
            // Clear existing data
            ThreadColor::truncate();
            $this->info('Cleared existing thread colors');
            
            $imported = 0;
            $downloadedCount = 0;
            $errors = [];
            
            foreach ($imageFiles as $index => $file) {
                try {
                    $filename = $file['name'];
                    $threadNumber = pathinfo($filename, PATHINFO_FILENAME);
                    
                    $this->info("Processing {$filename} (" . ($index + 1) . "/{$limit})");
                    
                    // Download and store the image
                    $imagePath = $dropboxService->downloadAndStoreImage($folderUrl, $threadNumber);
                    
                    if ($imagePath) {
                        $downloadedCount++;
                        $this->info("  ✓ Downloaded image for {$threadNumber}");
                    } else {
                        // Use placeholder if download fails
                        $imagePath = "https://via.placeholder.com/120x59/cccccc/000000?text={$threadNumber}";
                        $this->warn("  ⚠ Using placeholder for {$threadNumber}");
                    }
                    
                    // Create thread color record
                    ThreadColor::create([
                        'color_name' => $threadNumber,
                        'color_code' => $threadNumber,
                        'image_url' => $imagePath,
                    ]);
                    
                    $imported++;
                    
                    // Small delay to prevent rate limiting
                    usleep(200000); // 0.2 second delay
                    
                } catch (\Exception $e) {
                    $errors[] = "File {$filename}: " . $e->getMessage();
                    $this->error("  ✗ Error with {$filename}: " . $e->getMessage());
                }
            }
            
            $this->info('');
            $this->info("✅ Import completed successfully!");
            $this->info("📊 Imported: {$imported} thread colors");
            $this->info("🖼️ Downloaded: {$downloadedCount} images from Dropbox");
            
            if (!empty($errors)) {
                $this->warn("⚠️ Errors: " . count($errors) . " items had issues");
                foreach ($errors as $error) {
                    $this->warn("  - {$error}");
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}










