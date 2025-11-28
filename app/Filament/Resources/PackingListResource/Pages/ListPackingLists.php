<?php

namespace App\Filament\Resources\PackingListResource\Pages;

use App\Filament\Resources\PackingListResource;
use App\Models\IncomingShipment;
use App\Models\PackingListRecord;
use App\Models\SockStyle;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListPackingLists extends ListRecords
{
    protected static string $resource = PackingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadPackingList')
                ->label('Upload Packing List')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('shipment_id')
                        ->label('Incoming Shipment')
                        ->options(function () {
                            return IncomingShipment::orderBy('name')->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('Select an incoming shipment')
                        ->helperText('Select the shipment this packing list belongs to'),
                    Forms\Components\FileUpload::make('packing_list')
                        ->label('Packing List PDF')
                        ->acceptedFileTypes(['application/pdf'])
                        ->disk('local')
                        ->directory('imports')
                        ->required()
                        ->helperText('Upload PDF file with columns: Description, # Required'),
                ])
                ->action(function (array $data) {
                    $shipmentId = $data['shipment_id'] ?? null;
                    $file = $data['packing_list'] ?? null;
                    
                    if (!$shipmentId || !$file) {
                        Notification::make()
                            ->title('Error')
                            ->body('Please select a shipment and upload a file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $shipment = IncomingShipment::find($shipmentId);
                    if (!$shipment) {
                        Notification::make()
                            ->title('Error')
                            ->body('Shipment not found.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Get file path and original filename
                    $originalFilename = '';
                    if ($file instanceof TemporaryUploadedFile) {
                        $filePath = $file->getRealPath();
                        $originalFilename = $file->getClientOriginalName();
                    } elseif (is_string($file)) {
                        if (Storage::disk('local')->exists('imports/' . basename($file))) {
                            $filePath = Storage::disk('local')->path('imports/' . basename($file));
                            $originalFilename = basename($file);
                        } else {
                            $filePath = storage_path('app/' . $file);
                            $originalFilename = basename($file);
                        }
                    } else {
                        Notification::make()
                            ->title('Error')
                            ->body('Invalid file format.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    if (!file_exists($filePath)) {
                        Notification::make()
                            ->title('Error')
                            ->body('File not found.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Parse PDF
                    $parsedData = $this->parsePackingListPdf($filePath);
                    
                    if (empty($parsedData['items'])) {
                        $errorMessage = 'The PDF file did not contain any valid items.';
                        if (!empty($parsedData['debug_info'])) {
                            $errorMessage .= ' ' . $parsedData['debug_info'];
                        }
                        
                        Notification::make()
                            ->title('No items found')
                            ->body($errorMessage)
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Create pick list
                    $pickLists = $shipment->pick_lists ?? [];
                    if (!is_array($pickLists)) {
                        $pickLists = [];
                    }
                    
                    $pickList = [
                        'name' => $parsedData['name'] ?? 'Pick List ' . (count($pickLists) + 1),
                        'filename' => $originalFilename ?: basename($filePath),
                        'uploaded_at' => now()->toIso8601String(),
                        'status' => 'not_picked', // Will be updated automatically when items are picked
                        'items' => $parsedData['items'],
                        'picked_items' => [],
                    ];
                    
                    $pickLists[] = $pickList;
                    $shipment->pick_lists = $pickLists;
                    $shipment->save();
                    
                    // Clean up uploaded file
                    if (is_string($file) && Storage::disk('local')->exists('imports/' . basename($file))) {
                        Storage::disk('local')->delete('imports/' . basename($file));
                    }
                    
                    Notification::make()
                        ->title('Packing list uploaded')
                        ->body(count($parsedData['items']) . ' item(s) added from packing list.')
                        ->success()
                        ->send();
                    
                    // Refresh the page to show the new packing list
                    $this->dispatch('$refresh');
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Return a dummy query - we'll override getTableRecords instead
        return IncomingShipment::query()->whereRaw('1 = 0');
    }

    protected function getTableRecordUrlUsing(): ?\Closure
    {
        return function ($record) {
            if (!$record || !isset($record->shipment_id) || !isset($record->index)) {
                return null;
            }
            
            return PackingListResource::getUrl('view', [
                'shipmentId' => $record->shipment_id,
                'pickListIndex' => $record->index,
            ]);
        };
    }

    public function getTableRecords(): Collection|Paginator
    {
        // Get filter values
        $tableFilters = $this->getTable()->getFilters();
        $shipmentFilter = null;
        $statusFilter = null;
        
        foreach ($tableFilters as $filter) {
            $filterName = $filter->getName();
            $filterState = $filter->getState();
            
            if ($filterName === 'shipment_id' && !empty($filterState['value'])) {
                $shipmentFilter = $filterState['value'];
            }
            if ($filterName === 'status' && !empty($filterState['value'])) {
                $statusFilter = $filterState['value'];
            }
        }
        
        // Get shipments - filter by shipment_id if specified
        $shipmentsQuery = IncomingShipment::query();
        if ($shipmentFilter) {
            $shipmentsQuery->where('id', $shipmentFilter);
        }
        $shipments = $shipmentsQuery->get();
        
        // Build a collection of all packing lists with shipment context
        $allPackingLists = collect();
        
        foreach ($shipments as $shipment) {
            $pickLists = $shipment->pick_lists ?? [];
            if (!is_array($pickLists)) {
                continue;
            }
            
            foreach ($pickLists as $index => $pickList) {
                $items = $pickList['items'] ?? [];
                $itemCount = count($items);
                
                $totalNeeded = 0;
                foreach ($items as $item) {
                    $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
                }
                
                // Total quantity is the sum of all quantities, not the count of items
                $totalQuantity = $totalNeeded;
                
                $pickedItems = $pickList['picked_items'] ?? [];
                $totalPicked = 0;
                foreach ($pickedItems as $picked) {
                    $totalPicked += $picked['quantity_picked'] ?? 0;
                }
                
                $progressPercent = $totalNeeded > 0 ? round(($totalPicked / $totalNeeded) * 100) : 0;
                
                // Calculate status based on picked quantities
                $status = 'not_picked';
                if ($totalNeeded > 0) {
                    if ($totalPicked >= $totalNeeded) {
                        $status = 'fully_picked';
                    } elseif ($totalPicked > 0) {
                        $status = 'partially_picked';
                    }
                }
                
                // Use stored status if it exists and is valid, otherwise use calculated status
                $storedStatus = $pickList['status'] ?? null;
                if (in_array($storedStatus, ['not_picked', 'partially_picked', 'fully_picked', 'pending', 'in_progress', 'completed', 'picked'])) {
                    // Map old statuses to new ones for backward compatibility
                    if ($storedStatus === 'completed' || $storedStatus === 'picked') {
                        $status = 'fully_picked';
                    } elseif ($storedStatus === 'in_progress') {
                        $status = 'partially_picked';
                    } elseif ($storedStatus === 'pending') {
                        $status = 'not_picked';
                    } else {
                        $status = $storedStatus;
                    }
                }
                
                $allPackingLists->push(PackingListRecord::fromArray([
                    'id' => $shipment->id . '_' . $index, // Composite ID
                    'shipment_id' => $shipment->id,
                    'index' => $index,
                    'name' => $pickList['name'] ?? 'Pick List ' . ($index + 1),
                    'shipment_name' => $shipment->name ?? 'Shipment #' . $shipment->id,
                    'filename' => $pickList['filename'] ?? 'Unknown',
                    'uploaded_at' => $pickList['uploaded_at'] ?? null,
                    'status' => $status,
                    'item_count' => $totalQuantity, // Show total quantity instead of line item count
                    'total_needed' => $totalNeeded,
                    'total_picked' => $totalPicked,
                    'progress_percent' => $progressPercent,
                ]));
            }
        }
        
        // Apply status filter if specified
        if ($statusFilter) {
            $allPackingLists = $allPackingLists->filter(function ($record) use ($statusFilter) {
                $recordStatus = is_object($record) ? $record->status : ($record['status'] ?? 'not_picked');
                
                // Map old statuses for backward compatibility
                $normalizedStatus = match($recordStatus) {
                    'completed', 'picked' => 'fully_picked',
                    'in_progress' => 'partially_picked',
                    'pending' => 'not_picked',
                    default => $recordStatus,
                };
                
                // Map filter value for backward compatibility
                $normalizedFilter = match($statusFilter) {
                    'completed', 'picked' => 'fully_picked',
                    'in_progress' => 'partially_picked',
                    'pending' => 'not_picked',
                    default => $statusFilter,
                };
                
                return $normalizedStatus === $normalizedFilter;
            });
        }
        
        // Sort by uploaded_at descending and convert to Eloquent Collection
        $sorted = $allPackingLists->sortByDesc('uploaded_at')->values();
        
        // Convert to Eloquent Collection
        return Collection::make($sorted->all());
    }

    protected function parsePackingListPdf(string $filePath): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            \Log::info('PDF Text Content: ' . substr($text, 0, 1000));
            
            // Split text into lines
            $lines = explode("\n", $text);
            
            $items = [];
            $orderNumber = '';
            $pickListName = '';
            $headerFound = false;
            $descriptionColumnIndex = -1;
            $quantityColumnIndex = -1;
            
            // Find header row and extract order number
            foreach ($lines as $index => $line) {
                $lineTrimmed = trim($line);
                
                // Look for "Orders: BDR1399" pattern
                if (preg_match('/Orders?:\s*([A-Z0-9]+)/i', $lineTrimmed, $matches)) {
                    $orderNumber = $matches[1];
                    $pickListName = 'Pick List - ' . $orderNumber;
                }
                
                // Look for table header: Item # | Description | Warehouse Location | # Required
                if (preg_match('/Item\s*#|Description|Warehouse|Required/i', $lineTrimmed)) {
                    $headerFound = true;
                    
                    // Try to parse header to find column positions
                    // Split by multiple spaces, tabs, or pipes
                    $headerParts = preg_split('/\s{2,}|\t|\|/', $lineTrimmed);
                    $headerParts = array_map('trim', $headerParts);
                    $headerParts = array_filter($headerParts);
                    $headerParts = array_values($headerParts);
                    
                    foreach ($headerParts as $colIndex => $col) {
                        $colLower = strtolower($col);
                        if (preg_match('/description/i', $colLower)) {
                            $descriptionColumnIndex = $colIndex;
                        } elseif (preg_match('/required|#/i', $colLower)) {
                            $quantityColumnIndex = $colIndex;
                        }
                    }
                    
                    continue;
                }
                
                // Skip empty lines
                if (empty($lineTrimmed)) {
                    continue;
                }
                
                // Skip header-like lines
                if (preg_match('/^Item\s*#|^Description|^Warehouse|^Required|^---|^Total/i', $lineTrimmed)) {
                    continue;
                }
                
                // Skip "Orders: BDR1399" lines
                if (preg_match('/Orders?:\s*[A-Z0-9]+/i', $lineTrimmed)) {
                    continue;
                }
                
                // Parse data rows after header
                if ($headerFound) {
                    // Split by multiple spaces, tabs, or pipes
                    $parts = preg_split('/\s{2,}|\t|\|/', $lineTrimmed);
                    $parts = array_map('trim', $parts);
                    $parts = array_filter($parts, function($part) {
                        return !empty($part);
                    });
                    $parts = array_values($parts);
                    
                    if (count($parts) < 2) {
                        continue;
                    }
                    
                    $description = '';
                    $quantity = 0;
                    
                    // If we found column indices, use them
                    if ($descriptionColumnIndex >= 0 && isset($parts[$descriptionColumnIndex])) {
                        $description = trim($parts[$descriptionColumnIndex]);
                    }
                    
                    if ($quantityColumnIndex >= 0 && isset($parts[$quantityColumnIndex])) {
                        $quantityStr = trim($parts[$quantityColumnIndex]);
                        if (is_numeric($quantityStr)) {
                            $quantity = (int) $quantityStr;
                        }
                    }
                    
                    // Fallback: try to find description and quantity manually
                    if (empty($description) || $quantity === 0) {
                        // Look for numeric value (quantity) - usually last or second to last
                        $numericParts = [];
                        $textParts = [];
                        
                        foreach ($parts as $part) {
                            $partTrimmed = trim($part);
                            if (is_numeric($partTrimmed) && (int)$partTrimmed > 0) {
                                $numericParts[] = (int) $partTrimmed;
                            } else {
                                $textParts[] = $partTrimmed;
                            }
                        }
                        
                        // Quantity is usually the largest numeric value or the last one
                        if (!empty($numericParts)) {
                            $quantity = max($numericParts);
                        }
                        
                        // Description is all text parts joined
                        if (!empty($textParts)) {
                            $description = trim(implode(' ', $textParts));
                        }
                        
                        // If we have 4 parts, assume format: [empty, Description, empty, Quantity]
                        if (count($parts) === 4 && empty($description)) {
                            $description = trim($parts[1] ?? '');
                            if (empty($quantity) && isset($parts[3])) {
                                $quantity = is_numeric($parts[3]) ? (int)$parts[3] : 0;
                            }
                        }
                    }
                    
                    // Skip if no description or quantity
                    if (empty($description) || $quantity <= 0) {
                        continue;
                    }
                    
                    // Clean up description - remove extra whitespace
                    $description = preg_replace('/\s+/', ' ', $description);
                    
                    \Log::info('Parsed item - Description: ' . $description . ', Quantity: ' . $quantity);
                    
                    // Find matching SockStyle product - try exact match first
                    $product = SockStyle::where('name', $description)->first();
                    
                    if (!$product) {
                        \Log::info('Exact match not found, trying variations for: ' . $description);
                        
                        // Try trimming and normalizing
                        $normalizedDescription = trim($description);
                        $product = SockStyle::where('name', $normalizedDescription)->first();
                        
                        if (!$product) {
                            // Try case-insensitive match
                            $product = SockStyle::whereRaw('LOWER(name) = ?', [strtolower($normalizedDescription)])->first();
                        }
                        
                        if (!$product) {
                            // Try partial match - description contains product name
                            $allProducts = SockStyle::all();
                            foreach ($allProducts as $prod) {
                                // Check if description matches product name (case-insensitive)
                                if (stripos($normalizedDescription, $prod->name) !== false || 
                                    stripos($prod->name, $normalizedDescription) !== false) {
                                    $product = $prod;
                                    \Log::info('Found partial match: ' . $prod->name . ' for ' . $description);
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (!$product) {
                        \Log::warning('No product match found for: ' . $description);
                        // Still create item but without product_id - user can match manually later
                        // Extract style/color/packing way from description
                        $nameParts = explode(' - ', $description);
                        $style = '';
                        $color = '';
                        $packingWay = 'Hook';
                        
                        // Check for packing way in description
                        if (preg_match('/\b(Hook|Sleeve Wrap|Elastic Loop)\b/i', $description, $matches)) {
                            $packingWay = ucwords(strtolower($matches[1]));
                            // Remove packing way from description for parsing
                            $descriptionWithoutPacking = preg_replace('/\s*-\s*' . preg_quote($matches[1], '/') . '$/i', '', $description);
                            $nameParts = explode(' - ', $descriptionWithoutPacking);
                        }
                        
                        if (count($nameParts) >= 2) {
                            $style = trim($nameParts[0]);
                            $color = trim($nameParts[1]);
                        } else {
                            $style = trim($description);
                            $color = '';
                        }
                        
                        $items[] = [
                            'style' => $style,
                            'color' => $color,
                            'packing_way' => $packingWay,
                            'quantity_required' => $quantity,
                            'quantity' => $quantity,
                            'description' => $description, // Keep original description for reference
                        ];
                        continue;
                    }
                    
                    // Parse product name to extract style, color, and packing way
                    $name = $product->name;
                    $packagingStyle = $product->packaging_style ?? '';
                    
                    $nameWithoutPackaging = $name;
                    if (!empty($packagingStyle)) {
                        $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
                    }
                    
                    $nameParts = explode(' - ', $nameWithoutPackaging);
                    $style = '';
                    $color = '';
                    if (count($nameParts) >= 2) {
                        $style = trim($nameParts[0]);
                        $color = trim($nameParts[1]);
                    } else {
                        $style = trim($nameWithoutPackaging);
                        $color = '';
                    }
                    
                    $items[] = [
                        'product_id' => $product->id,
                        'style' => $style,
                        'color' => $color,
                        'packing_way' => $packagingStyle ?: 'Hook',
                        'quantity_required' => $quantity,
                        'quantity' => $quantity,
                    ];
                }
            }
            
            \Log::info('Parsed ' . count($items) . ' items from PDF');
            
            // If no order number found, use filename or default
            if (empty($pickListName)) {
                $filename = basename($filePath);
                $pickListName = 'Pick List - ' . pathinfo($filename, PATHINFO_FILENAME);
            }
            
            $debugInfo = '';
            if (empty($items)) {
                $debugInfo = 'Found ' . count($lines) . ' lines in PDF. Header found: ' . ($headerFound ? 'yes' : 'no');
            }
            
            return [
                'name' => $pickListName,
                'items' => $items,
                'debug_info' => $debugInfo,
            ];
        } catch (\Exception $e) {
            \Log::error('Error parsing packing list PDF: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return [
                'name' => 'Pick List',
                'items' => [],
            ];
        }
    }
}
