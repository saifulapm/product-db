<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use App\Models\IncomingShipment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListIncomingShipments extends ListRecords
{
    protected static string $resource = IncomingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_packing_list')
                ->label('Import Packing List')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->form([
                    Forms\Components\Section::make('Shipment Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Shipment Name')
                                ->maxLength(255)
                                ->placeholder('e.g., BDR1399 Shipment, November Order, etc.')
                                ->helperText('A descriptive name for this shipment')
                                ->extraAttributes(['id' => 'import_packing_list_name', 'name' => 'name']),
                            Forms\Components\TextInput::make('tracking_number')
                                ->label('Tracking Number')
                                ->maxLength(255)
                                ->extraAttributes(['id' => 'import_packing_list_tracking_number', 'name' => 'tracking_number']),
                            Forms\Components\TextInput::make('carrier')
                                ->label('Carrier')
                                ->maxLength(255)
                                ->extraAttributes(['id' => 'import_packing_list_carrier', 'name' => 'carrier']),
                            Forms\Components\TextInput::make('supplier')
                                ->label('Supplier')
                                ->maxLength(255)
                                ->extraAttributes(['id' => 'import_packing_list_supplier', 'name' => 'supplier']),
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in_transit' => 'In Transit',
                                    'received' => 'Received',
                                    'delayed' => 'Delayed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->required()
                                ->extraAttributes(['id' => 'import_packing_list_status', 'name' => 'status']),
                        ])
                        ->columns(2),
                    Forms\Components\Section::make('Packing List File')
                        ->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('Packing List File')
                                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'])
                                ->required()
                                ->helperText('Upload CSV, Excel, or PDF file with columns: CTN#, STYLE, COLOR, PACKING WAY, #PC/CTN')
                                ->disk('local')
                                ->directory('imports')
                                ->visibility('private')
                                ->multiple(false)
                                ->maxFiles(1)
                                ->downloadable(false)
                                ->openable(false)
                                ->previewable(false)
                                ->extraAttributes(['id' => 'import_packing_list_file', 'name' => 'file']),
                        ]),
                ])
                ->action(function (array $data) {
                    // Handle file path - Filament FileUpload stores files automatically
                    $filePath = null;
                    $fileToDelete = null;
                    
                    // Handle array (shouldn't happen with single file, but be safe)
                    $file = is_array($data['file']) ? $data['file'][0] : $data['file'];
                    
                    if ($file instanceof TemporaryUploadedFile) {
                        // File is still temporary - store it first
                        $storedPath = $file->storeAs('imports', $file->getClientOriginalName(), 'local');
                        $filePath = Storage::disk('local')->path($storedPath);
                        $fileToDelete = $storedPath;
                    } elseif (is_string($file)) {
                        // File has been stored by Filament - path is relative to disk root
                        // Check if file exists in storage
                        if (Storage::disk('local')->exists($file)) {
                            $filePath = Storage::disk('local')->path($file);
                            $fileToDelete = $file;
                        } else {
                            // Try alternative paths
                            $alternatives = [
                                $file,
                                'imports/' . basename($file),
                                ltrim($file, '/'),
                            ];
                            
                            foreach ($alternatives as $altPath) {
                                if (Storage::disk('local')->exists($altPath)) {
                                    $filePath = Storage::disk('local')->path($altPath);
                                    $fileToDelete = $altPath;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (!$filePath || !file_exists($filePath)) {
                        \Log::error('File upload error', [
                            'file_data_type' => gettype($data['file']),
                            'file_value' => is_string($data['file']) ? $data['file'] : 'not-string',
                            'file_path' => $filePath,
                            'storage_files' => Storage::disk('local')->allFiles('imports'),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('File not found')
                            ->body('Could not locate the uploaded file. Please try uploading again.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $items = $this->parsePackingListFile($filePath);
                    
                    if (empty($items)) {
                        \Filament\Notifications\Notification::make()
                            ->title('No items found')
                            ->warning()
                            ->body('Could not parse any items from the file. Please check the format.')
                            ->send();
                        
                        // Clean up file
                        if ($fileToDelete && is_string($fileToDelete)) {
                            Storage::disk('local')->delete($fileToDelete);
                        } elseif ($fileToDelete && file_exists($fileToDelete)) {
                            @unlink($fileToDelete);
                        }
                        return;
                    }
                    
                    // Create new shipment with imported data
                    $shipment = IncomingShipment::create([
                        'name' => $data['name'] ?? null,
                        'tracking_number' => $data['tracking_number'] ?? null,
                        'carrier' => $data['carrier'] ?? null,
                        'supplier' => $data['supplier'] ?? null,
                        'status' => $data['status'] ?? 'pending',
                        'items' => $items,
                        'created_by' => auth()->id(),
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Shipment created')
                        ->success()
                        ->body('Created shipment with ' . count($items) . ' items imported from packing list.')
                        ->send();
                    
                    // Clean up uploaded file
                    if ($fileToDelete && is_string($fileToDelete)) {
                        Storage::disk('local')->delete($fileToDelete);
                    } elseif ($fileToDelete && file_exists($fileToDelete)) {
                        @unlink($fileToDelete);
                    }
                    
                    // Redirect to view the new shipment
                    $this->redirect(IncomingShipmentResource::getUrl('view', ['record' => $shipment]));
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function parsePackingListFile(string $filePath): array
    {
        $items = [];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $items = $this->parseCsvFile($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $items = $this->parseExcelFile($filePath);
        } elseif ($extension === 'pdf') {
            $items = $this->parsePdfFile($filePath);
        }
        
        return $items;
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
            $colLower = strtolower(trim($col));
            
            // Check for CTN# / Carton Number first (most specific)
            if (preg_match('/ctn|carton/i', $col) && preg_match('/#|number|num/i', $col)) {
                $headerMap['carton'] = $index;
            } elseif (preg_match('/^style|^styl/i', $colLower)) {
                $headerMap['style'] = $index;
            } elseif (preg_match('/^color|^colour/i', $colLower)) {
                $headerMap['color'] = $index;
            } elseif (preg_match('/packing.*way|way.*packing/i', $colLower)) {
                $headerMap['packing_way'] = $index;
            } elseif (preg_match('/pc\/ctn|#pc|quantity|qty|pieces/i', $colLower)) {
                // More specific quantity detection - avoid matching CTN#
                $headerMap['quantity'] = $index;
            }
        }
        
        // Fallback: positional mapping
        if (empty($headerMap) && count($header) >= 5) {
            $headerMap = [
                'carton' => 0,
                'style' => 1,
                'color' => 2,
                'packing_way' => 3,
                'quantity' => 4,
            ];
        }
        
        // Read data rows
        $lastCarton = '';
        $cartonCounter = 0; // Track carton numbers starting from 1
        $seenCartons = []; // Track unique carton values to map to sequential numbers
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }
            
            $cartonRaw = isset($headerMap['carton']) ? trim($row[$headerMap['carton']] ?? '') : '';
            $style = isset($headerMap['style']) ? trim($row[$headerMap['style']] ?? '') : '';
            $color = isset($headerMap['color']) ? trim($row[$headerMap['color']] ?? '') : '';
            $packingWay = isset($headerMap['packing_way']) ? trim($row[$headerMap['packing_way']] ?? '') : '';
            $quantity = isset($headerMap['quantity']) ? trim($row[$headerMap['quantity']] ?? '') : '';
            
            // Skip empty rows
            if (empty($style) && empty($color) && empty($quantity)) {
                continue;
            }
            
            // Handle carton number - normalize to sequential numbers starting from 1
            $carton = '';
            if (!empty($cartonRaw)) {
                // If we haven't seen this carton value before, assign it the next sequential number
                if (!isset($seenCartons[$cartonRaw])) {
                    $cartonCounter++;
                    $seenCartons[$cartonRaw] = $cartonCounter;
                }
                $carton = (string)$seenCartons[$cartonRaw];
                $lastCarton = $carton;
            } elseif (!empty($lastCarton) && (!empty($style) || !empty($color))) {
                // If carton is empty but we have style/color, use last carton
                $carton = $lastCarton;
            } else {
                // If no carton info and no last carton, skip this row
                continue;
            }
            
            // Normalize quantity - extract numeric value
            $quantityValue = 0;
            if (!empty($quantity)) {
                // Extract numbers from quantity field
                preg_match('/\d+/', $quantity, $matches);
                $quantityValue = !empty($matches) ? (int)$matches[0] : 0;
            }
            
            // Normalize packing way
            $packingWay = !empty($packingWay) 
                ? (strtolower(trim($packingWay)) === 'hook' ? 'Hook' : trim($packingWay)) 
                : 'Hook';
            
            // Preserve full style text (may contain spaces, hyphens, etc.)
            $style = trim($style);
            $color = trim($color);
            
            $items[] = [
                'carton_number' => $carton,
                'style' => $style,
                'color' => $color,
                'packing_way' => $packingWay,
                'quantity' => $quantityValue > 0 ? $quantityValue : 0,
            ];
        }
        
        fclose($handle);
        return $items;
    }

    protected function parseExcelFile(string $filePath): array
    {
        return $this->parseCsvFile($filePath);
    }

    protected function parsePdfFile(string $filePath): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            $lines = explode("\n", $text);
            
            $headerIndex = -1;
            $headerMap = [];
            
            // Find header row
            foreach ($lines as $index => $line) {
                $lineLower = strtolower(trim($line));
                
                // Look for header row with CTN and STYLE/COLOR
                if (preg_match('/ctn|carton/i', $line) && 
                    (preg_match('/style/i', $line) || preg_match('/color/i', $line))) {
                    $headerIndex = $index;
                    
                    // Try splitting by pipes first (common in PDF tables)
                    $headerParts = preg_split('/\s*\|\s*/', $line);
                    if (count($headerParts) < 3) {
                        // Fallback to multiple spaces or tabs
                        $headerParts = preg_split('/\s{2,}|\t/', $line);
                    }
                    
                    foreach ($headerParts as $colIndex => $col) {
                        $colLower = strtolower(trim($col));
                        if (preg_match('/ctn|carton/i', $col) && preg_match('/#|number/i', $col)) {
                            $headerMap['carton'] = $colIndex;
                        } elseif (preg_match('/^style/i', $colLower)) {
                            $headerMap['style'] = $colIndex;
                        } elseif (preg_match('/^color/i', $colLower)) {
                            $headerMap['color'] = $colIndex;
                        } elseif (preg_match('/packing.*way|way.*packing/i', $colLower)) {
                            $headerMap['packing_way'] = $colIndex;
                        } elseif (preg_match('/pc\/ctn|#pc|quantity|qty|pieces/i', $colLower)) {
                            $headerMap['quantity'] = $colIndex;
                        }
                    }
                    break;
                }
            }
            
            // Fallback: assume standard column order if header not found
            if ($headerIndex === -1) {
                foreach ($lines as $index => $line) {
                    $parts = preg_split('/\s*\|\s*/', trim($line));
                    if (count($parts) < 3) {
                        $parts = preg_split('/\s{2,}|\t/', trim($line));
                    }
                    if (count($parts) >= 4 && is_numeric(trim($parts[0]))) {
                        $headerIndex = $index - 1;
                        $headerMap = [
                            'carton' => 0,
                            'style' => 1,
                            'color' => 2,
                            'packing_way' => 3,
                            'quantity' => 4,
                        ];
                        break;
                    }
                }
            }
            
            if ($headerIndex === -1) {
                \Log::error('PDF parsing: Could not find header row');
                return [];
            }
            
            $items = [];
            $lastCarton = '';
            
            // Process data rows
            for ($i = $headerIndex + 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                
                // Skip empty lines, header repeats, separators
                if (empty($line) || 
                    preg_match('/^ctn\s*#|^style|^color|^packing|^---|^bodyrok/i', $line) ||
                    preg_match('/^\s*\|+\s*$/', $line)) {
                    continue;
                }
                
                // Try splitting by pipes first (common in PDF tables)
                $parts = preg_split('/\s*\|\s*/', $line);
                if (count($parts) < 3) {
                    // Fallback to multiple spaces (2+ spaces)
                    $parts = preg_split('/\s{2,}/', $line);
                }
                
                // If still not enough parts, try single space but be more careful
                if (count($parts) < 3) {
                    // This might be a single-line entry, skip it
                    continue;
                }
                
                // Extract values based on header map
                $cartonRaw = '';
                if (isset($headerMap['carton']) && isset($parts[$headerMap['carton']])) {
                    $cartonRaw = trim($parts[$headerMap['carton']]);
                }
                
                $style = '';
                if (isset($headerMap['style']) && isset($parts[$headerMap['style']])) {
                    $style = trim($parts[$headerMap['style']]);
                }
                
                $color = '';
                if (isset($headerMap['color']) && isset($parts[$headerMap['color']])) {
                    $color = trim($parts[$headerMap['color']]);
                }
                
                $packingWay = '';
                if (isset($headerMap['packing_way']) && isset($parts[$headerMap['packing_way']])) {
                    $packingWay = trim($parts[$headerMap['packing_way']]);
                }
                
                $quantityRaw = '';
                if (isset($headerMap['quantity']) && isset($parts[$headerMap['quantity']])) {
                    $quantityRaw = trim($parts[$headerMap['quantity']]);
                }
                
                // Skip rows with no meaningful data
                if (empty($style) && empty($color) && empty($quantityRaw)) {
                    continue;
                }
                
                // Handle carton number - use actual carton number from PDF, not normalize
                $carton = '';
                if (!empty($cartonRaw) && is_numeric($cartonRaw)) {
                    // Use the actual carton number from the PDF
                    $carton = (string)(int)$cartonRaw;
                    $lastCarton = $carton;
                } elseif (!empty($lastCarton) && (!empty($style) || !empty($color))) {
                    // If carton is empty but we have style/color, use last carton
                    $carton = $lastCarton;
                } else {
                    // Skip rows without carton info
                    continue;
                }
                
                // Extract quantity - get numeric value
                $quantityValue = 0;
                if (!empty($quantityRaw)) {
                    preg_match('/\d+/', $quantityRaw, $matches);
                    $quantityValue = !empty($matches) ? (int)$matches[0] : 0;
                }
                
                // Skip if no quantity
                if ($quantityValue <= 0) {
                    continue;
                }
                
                // Normalize packing way
                $packingWay = !empty($packingWay) 
                    ? (strtolower(trim($packingWay)) === 'hook' ? 'Hook' : trim($packingWay)) 
                    : 'Hook';
                
                // Preserve full style and color text (may contain spaces, slashes, etc.)
                $style = trim($style);
                $color = trim($color);
                
                // Skip if no style or color
                if (empty($style) && empty($color)) {
                    continue;
                }
                
                $items[] = [
                    'carton_number' => $carton,
                    'style' => $style,
                    'color' => $color,
                    'packing_way' => $packingWay,
                    'quantity' => $quantityValue,
                ];
            }
            
            \Log::info('PDF parsing: Parsed ' . count($items) . ' items from PDF');
            return $items;
        } catch (\Exception $e) {
            \Log::error('Error parsing PDF: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return [];
        }
    }
}
