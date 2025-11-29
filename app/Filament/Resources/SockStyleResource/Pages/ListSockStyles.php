<?php

namespace App\Filament\Resources\SockStyleResource\Pages;

use App\Filament\Resources\SockStyleResource;
use App\Models\SockStyle;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListSockStyles extends ListRecords
{
    protected static string $resource = SockStyleResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pasteNames')
                ->label('Paste Names')
                ->icon('heroicon-o-clipboard-document')
                ->color('info')
                ->form([
                    Forms\Components\Textarea::make('names')
                        ->label('Sock Product Names')
                        ->required()
                        ->rows(10)
                        ->placeholder('Paste sock names, one per line:' . PHP_EOL . 'Athletic Crew' . PHP_EOL . 'Dress Socks' . PHP_EOL . 'Ankle Socks')
                        ->helperText('Enter one product name per line. Each name will be created with all 3 packaging styles (Hook, Sleeve Wrap, Elastic Loop).'),
                ])
                ->action(function (array $data) {
                    $namesText = $data['names'] ?? '';
                    
                    if (empty($namesText)) {
                        Notification::make()
                            ->title('No names provided')
                            ->body('Please paste at least one product name.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Split by newlines and filter empty lines
                    $names = array_filter(
                        array_map('trim', explode("\n", $namesText)),
                        fn($name) => !empty($name)
                    );
                    
                    if (empty($names)) {
                        Notification::make()
                            ->title('No valid names found')
                            ->body('Could not find any valid product names in the pasted text.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $packagingStyles = ['Hook', 'Sleeve Wrap', 'Elastic Loop'];
                    $created = 0;
                    $skipped = 0;
                    
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) {
                            continue;
                        }
                        
                        foreach ($packagingStyles as $packagingStyle) {
                            // Create a unique name by appending packaging style
                            $uniqueName = $name . ' - ' . $packagingStyle;
                            
                            // Check if this combination already exists
                            if (SockStyle::where('name', $uniqueName)->exists()) {
                                $skipped++;
                                continue;
                            }
                            
                            SockStyle::create([
                                'name' => $uniqueName,
                                'packaging_style' => $packagingStyle,
                                'is_active' => true,
                            ]);
                            
                            $created++;
                        }
                    }
                    
                    Notification::make()
                        ->title('Products created')
                        ->body("Successfully created {$created} product(s) across all packaging styles. " . ($skipped > 0 ? "Skipped {$skipped} duplicate(s)." : ""))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('bulkImport')
                ->label('Bulk Import from CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv'])
                        ->required()
                        ->helperText('Upload a CSV file with columns: Product Name, Packaging Style')
                        ->disk('local')
                        ->directory('temp-csv-imports')
                        ->visibility('private')
                        ->maxFiles(1),
                ])
                ->action(function (array $data) {
                    $file = $data['csv_file'];
                    $filePath = null;
                    
                    if ($file instanceof TemporaryUploadedFile) {
                        $storedPath = $file->storeAs('temp-csv-imports', $file->getClientOriginalName(), 'local');
                        $filePath = Storage::disk('local')->path($storedPath);
                    } elseif (is_string($file) && Storage::disk('local')->exists($file)) {
                        $filePath = Storage::disk('local')->path($file);
                    }
                    
                    if (!$filePath || !file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found')
                            ->body('Could not locate the uploaded file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $parsedData = $this->parseCsvFile($filePath);
                    
                    // Clean up temp file
                    if (isset($storedPath)) {
                        Storage::disk('local')->delete($storedPath);
                    }
                    
                    if (empty($parsedData)) {
                        Notification::make()
                            ->title('No data found')
                            ->body('Could not parse any data from the CSV file. Please check the file format.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $created = 0;
                    $skipped = 0;
                    
                    foreach ($parsedData as $item) {
                        // Skip if product name is empty
                        if (empty($item['name'])) {
                            $skipped++;
                            continue;
                        }
                        
                        // Check if product name already exists
                        if (SockStyle::where('name', $item['name'])->exists()) {
                            $skipped++;
                            continue;
                        }
                        
                        SockStyle::create([
                            'name' => $item['name'],
                            'packaging_style' => $item['packaging_style'] ?? null,
                            'is_active' => true,
                        ]);
                        
                        $created++;
                    }
                    
                    Notification::make()
                        ->title('Import completed')
                        ->body("Successfully imported {$created} product(s). " . ($skipped > 0 ? "Skipped {$skipped} duplicate(s) or empty row(s)." : ""))
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
    
    protected function parseCsvFile(string $filePath): array
    {
        $items = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return $items;
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return $items;
        }
        
        // Normalize header and map columns
        $headerMap = [];
        foreach ($header as $index => $col) {
            $colTrimmed = trim($col);
            $colLower = strtolower($colTrimmed);
            
            // Map headers: Product Name, Packaging Style
            if (preg_match('/product.*name|name/i', $colTrimmed)) {
                $headerMap['name'] = $index;
            } elseif (preg_match('/packaging.*style|packaging/i', $colLower)) {
                $headerMap['packaging_style'] = $index;
            }
        }
        
        // Fallback: positional mapping if header mapping failed
        if (empty($headerMap) && count($header) >= 2) {
            $headerMap = [
                'name' => 0,
                'packaging_style' => 1,
            ];
        }
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 1) {
                continue; // Skip empty rows
            }
            
            $item = [
                'name' => isset($headerMap['name']) && isset($row[$headerMap['name']]) 
                    ? trim($row[$headerMap['name']]) 
                    : '',
                'packaging_style' => isset($headerMap['packaging_style']) && isset($row[$headerMap['packaging_style']]) 
                    ? trim($row[$headerMap['packaging_style']]) 
                    : null,
            ];
            
            // Normalize packaging_style
            if (!empty($item['packaging_style'])) {
                $packagingStyle = strtolower(trim($item['packaging_style']));
                if (stripos($packagingStyle, 'hook') !== false) {
                    $item['packaging_style'] = 'Hook';
                } elseif (stripos($packagingStyle, 'sleeve') !== false || stripos($packagingStyle, 'wrap') !== false) {
                    $item['packaging_style'] = 'Sleeve Wrap';
                } elseif (stripos($packagingStyle, 'elastic') !== false || stripos($packagingStyle, 'loop') !== false) {
                    $item['packaging_style'] = 'Elastic Loop';
                } else {
                    $item['packaging_style'] = null;
                }
            }
            
            // Only add item if it has a product name
            if (!empty($item['name'])) {
                $items[] = $item;
            }
        }
        
        fclose($handle);
        return $items;
    }
}
