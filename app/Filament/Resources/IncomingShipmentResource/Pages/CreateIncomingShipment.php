<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use App\Models\SockStyle;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateIncomingShipment extends CreateRecord
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
                    Forms\Components\FileUpload::make('file')
                        ->label('Packing List File')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'])
                        ->required()
                        ->helperText('Upload CSV, Excel, or PDF file with columns: CTN#, STYLE, COLOR, PACKING WAY, #PC/CTN')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/' . $data['file']);
                    
                    if (!file_exists($filePath)) {
                        \Filament\Notifications\Notification::make()
                            ->title('File not found')
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
                        return;
                    }
                    
                    // Fill the form with imported items
                    $this->form->fill([
                        'items' => $items,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Packing list imported')
                        ->success()
                        ->body('Imported ' . count($items) . ' items. Review and save the shipment.')
                        ->send();
                    
                    // Clean up uploaded file
                    @unlink($filePath);
                }),
        ];
    }

    public function bulkAddProducts(): Actions\Action
    {
        return Actions\Action::make('bulkAddProducts')
            ->label('Bulk Add Products')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->form([
                Forms\Components\Select::make('product_ids')
                    ->label('Select Products')
                    ->options(function () {
                        return SockStyle::orderBy('name')->pluck('name', 'id');
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Search and select products')
                    ->helperText('Select multiple products to add to the shipment')
                    ->maxItems(100),
            ])
            ->action(function (array $data) {
                $productIds = $data['product_ids'] ?? [];
                
                if (empty($productIds)) {
                    Notification::make()
                        ->title('No products selected')
                        ->body('Please select at least one product.')
                        ->warning()
                        ->send();
                    return;
                }
                
                // Get current items from form
                $currentItems = $this->form->getState()['items'] ?? [];
                if (!is_array($currentItems)) {
                    $currentItems = [];
                }
                
                // Create new items from selected products
                $newItems = [];
                foreach ($productIds as $productId) {
                    $product = SockStyle::find($productId);
                    if (!$product) {
                        continue;
                    }
                    
                    // Parse the product name to extract style/color
                    $name = $product->name;
                    $packagingStyle = $product->packaging_style ?? '';
                    
                    // Remove packaging style from name if it's at the end
                    $nameWithoutPackaging = $name;
                    if (!empty($packagingStyle)) {
                        $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
                    }
                    
                    // Try to split style and color
                    $parts = explode(' - ', $nameWithoutPackaging);
                    $style = '';
                    $color = '';
                    if (count($parts) >= 2) {
                        $style = trim($parts[0]);
                        $color = trim($parts[1]);
                    } else {
                        $style = trim($nameWithoutPackaging);
                        $color = '';
                    }
                    
                    $newItems[] = [
                        'product_id' => $productId,
                        'carton_number' => '',
                        'style' => $style,
                        'color' => $color,
                        'packing_way' => $packagingStyle ?: 'Hook',
                        'quantity' => 1,
                    ];
                }
                
                // Merge new items with existing items
                $mergedItems = array_merge($currentItems, $newItems);
                
                // Update the form data
                $formData = $this->form->getState();
                $formData['items'] = $mergedItems;
                
                // Fill the form with updated data
                $this->form->fill($formData);
                
                Notification::make()
                    ->title('Products added')
                    ->body(count($newItems) . ' product(s) added to the shipment.')
                    ->success()
                    ->send();
            });
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Remove the _selected checkbox field and product_id from items before saving
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as &$item) {
                if (isset($item['_selected'])) {
                    unset($item['_selected']);
                }
                if (isset($item['product_id'])) {
                    unset($item['product_id']);
                }
            }
        }
        
        return $data;
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
            
            if (preg_match('/ctn|carton/i', $col) && preg_match('/#|number|num/i', $col)) {
                $headerMap['carton'] = $index;
            } elseif (preg_match('/^style|^styl/i', $colLower)) {
                $headerMap['style'] = $index;
            } elseif (preg_match('/^color|^colour/i', $colLower)) {
                $headerMap['color'] = $index;
            } elseif (preg_match('/packing.*way|way.*packing/i', $colLower)) {
                $headerMap['packing_way'] = $index;
            } elseif (preg_match('/pc|quantity|qty|pieces/i', $colLower) || preg_match('/#/i', $col)) {
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
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }
            
            $carton = isset($headerMap['carton']) ? trim($row[$headerMap['carton']] ?? '') : '';
            $style = isset($headerMap['style']) ? trim($row[$headerMap['style']] ?? '') : '';
            $color = isset($headerMap['color']) ? trim($row[$headerMap['color']] ?? '') : '';
            $packingWay = isset($headerMap['packing_way']) ? trim($row[$headerMap['packing_way']] ?? '') : '';
            $quantity = isset($headerMap['quantity']) ? (int) trim($row[$headerMap['quantity']] ?? 0) : 0;
            
            if (empty($style) && empty($color) && $quantity === 0) {
                continue;
            }
            
            // If carton is empty but we have a style/color, use last carton (continuing from previous row)
            if (empty($carton) && !empty($lastCarton) && (!empty($style) || !empty($color))) {
                $carton = $lastCarton;
            }
            
            // Update last carton if this row has one
            if (!empty($carton)) {
                $lastCarton = $carton;
            }
            
            $packingWay = !empty($packingWay) ? (strtolower($packingWay) === 'hook' ? 'Hook' : $packingWay) : 'Hook';
            
            $items[] = [
                'carton_number' => $carton,
                'style' => $style,
                'color' => $color,
                'packing_way' => $packingWay,
                'quantity' => $quantity > 0 ? $quantity : 0,
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
            
            // Split text into lines
            $lines = explode("\n", $text);
            
            // Find the table header row
            $headerIndex = -1;
            $headerMap = [];
            
            foreach ($lines as $index => $line) {
                $lineLower = strtolower(trim($line));
                
                // Look for header row with CTN#, STYLE, COLOR, etc.
                if (preg_match('/ctn|carton/i', $line) && 
                    (preg_match('/style/i', $line) || preg_match('/color/i', $line))) {
                    $headerIndex = $index;
                    
                    // Parse header to map columns
                    $headerParts = preg_split('/\s{2,}|\t/', $line);
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
                        } elseif (preg_match('/pc|quantity|qty|#/i', $colLower)) {
                            $headerMap['quantity'] = $colIndex;
                        }
                    }
                    break;
                }
            }
            
            // If header not found, try positional mapping
            if ($headerIndex === -1) {
                foreach ($lines as $index => $line) {
                    $parts = preg_split('/\s{2,}|\t/', trim($line));
                    if (count($parts) >= 4) {
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
                return [];
            }
            
            // Parse data rows
            $items = [];
            $lastCarton = '';
            
            for ($i = $headerIndex + 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                
                if (empty($line) || preg_match('/^ctn|^style|^color|^packing|^---/i', $line)) {
                    continue;
                }
                
                $parts = preg_split('/\s{2,}|\t/', $line);
                
                if (count($parts) < 3) {
                    continue;
                }
                
                $carton = isset($headerMap['carton']) && isset($parts[$headerMap['carton']]) 
                    ? trim($parts[$headerMap['carton']]) : '';
                $style = isset($headerMap['style']) && isset($parts[$headerMap['style']]) 
                    ? trim($parts[$headerMap['style']]) : '';
                $color = isset($headerMap['color']) && isset($parts[$headerMap['color']]) 
                    ? trim($parts[$headerMap['color']]) : '';
                $packingWay = isset($headerMap['packing_way']) && isset($parts[$headerMap['packing_way']]) 
                    ? trim($parts[$headerMap['packing_way']]) : '';
                $quantity = isset($headerMap['quantity']) && isset($parts[$headerMap['quantity']]) 
                    ? (int) trim($parts[$headerMap['quantity']]) : 0;
                
                if (empty($style) && empty($color) && $quantity === 0) {
                    continue;
                }
                
                if (empty($carton) && !empty($lastCarton) && (!empty($style) || !empty($color))) {
                    $carton = $lastCarton;
                }
                
                if (!empty($carton)) {
                    $lastCarton = $carton;
                }
                
                $packingWay = !empty($packingWay) ? (strtolower($packingWay) === 'hook' ? 'Hook' : $packingWay) : 'Hook';
                
                $items[] = [
                    'carton_number' => $carton,
                    'style' => $style,
                    'color' => $color,
                    'packing_way' => $packingWay,
                    'quantity' => $quantity > 0 ? $quantity : 0,
                ];
            }
            
            return $items;
        } catch (\Exception $e) {
            \Log::error('Error parsing PDF: ' . $e->getMessage());
            return [];
        }
    }
}
