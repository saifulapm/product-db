<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\SockStyle;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

class IncomingItemsTableWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    #[Reactive]
    public ?Model $record = null;

    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.incoming-items-table-widget';

    protected int | string | array $columnSpan = 'full';

    public array $selectedItems = [];
    
    public bool $selectAll = false;

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $items = $this->getViewData()['items'] ?? [];
            $this->selectedItems = array_column($items, 'index');
        } else {
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems(): void
    {
        // Sync selectAll checkbox when individual checkboxes change
        $items = $this->getViewData()['items'] ?? [];
        $allIndices = array_column($items, 'index');
        $this->selectAll = !empty($allIndices) && count($this->selectedItems) === count($allIndices);
    }

    public function mount(): void
    {
        // Cache actions - they will be created when accessed
    }
    
    public function addProductAction(): Actions\Action
    {
        return Actions\Action::make('addProduct')
            ->label('Add Product')
            ->icon('heroicon-o-plus')
            ->color('success')
            ->form([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->options(function () {
                        return SockStyle::orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->placeholder('Select a product')
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = SockStyle::find($state);
                            if ($product) {
                                $name = $product->name;
                                $packagingStyle = $product->packaging_style ?? '';
                                
                                $nameWithoutPackaging = $name;
                                if (!empty($packagingStyle)) {
                                    $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
                                }
                                
                                $parts = explode(' - ', $nameWithoutPackaging);
                                if (count($parts) >= 2) {
                                    $set('style', trim($parts[0]));
                                    $set('color', trim($parts[1]));
                                } else {
                                    $set('style', trim($nameWithoutPackaging));
                                    $set('color', '');
                                }
                                
                                if (!empty($packagingStyle)) {
                                    $set('packing_way', $packagingStyle);
                                }
                            }
                        }
                    }),
                Forms\Components\TextInput::make('carton_number')
                    ->label('CTN#')
                    ->maxLength(50)
                    ->placeholder('e.g., 1, 2, 3'),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Forms\Components\Hidden::make('style'),
                Forms\Components\Hidden::make('color'),
                Forms\Components\Hidden::make('packing_way'),
            ])
            ->action(function (array $data) {
                if (!$this->record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }
                
                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);
                
                if (!$record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }
                
                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                
                $items[] = $data;
                $record->update(['items' => $items]);
                
                // Dispatch event to refresh the parent component
                $this->dispatch('refresh');
                
                Notification::make()
                    ->title('Product added')
                    ->success()
                    ->send();
            });
    }

    public function bulkAddProductsAction(): Actions\Action
    {
        return Actions\Action::make('bulkAddProducts')
            ->label('Bulk Add Products')
            ->icon('heroicon-o-plus-circle')
            ->color('info')
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
                    ->maxItems(100),
            ])
            ->action(function (array $data) {
                if (!$this->record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }
                
                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);
                
                if (!$record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }
                
                $productIds = $data['product_ids'] ?? [];
                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                
                foreach ($productIds as $productId) {
                    $product = SockStyle::find($productId);
                    if (!$product) {
                        continue;
                    }
                    
                    $name = $product->name;
                    $packagingStyle = $product->packaging_style ?? '';
                    
                    $nameWithoutPackaging = $name;
                    if (!empty($packagingStyle)) {
                        $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
                    }
                    
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
                    
                    $items[] = [
                        'product_id' => $productId,
                        'carton_number' => '',
                        'style' => $style,
                        'color' => $color,
                        'packing_way' => $packagingStyle ?: 'Hook',
                        'quantity' => 1,
                    ];
                }
                
                $record->update(['items' => $items]);
                
                // Dispatch event to refresh the parent component
                $this->dispatch('refresh');
                
                Notification::make()
                    ->title('Products added')
                    ->body(count($productIds) . ' product(s) added.')
                    ->success()
                    ->send();
            });
    }

    public function uploadPackingListAction(): Actions\Action
    {
        return Actions\Action::make('uploadPackingList')
            ->label('Upload Packing List')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->form([
                Forms\Components\FileUpload::make('packing_list')
                    ->label('Packing List CSV')
                    ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('imports')
                    ->required()
                    ->helperText('Upload CSV file with columns: Product, Carton, Quantity'),
            ])
            ->action(function (array $data) {
                if (!$this->record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }

                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);

                if (!$record) {
                    Notification::make()
                        ->title('Error')
                        ->body('Record not found.')
                        ->danger()
                        ->send();
                    return;
                }

                $file = $data['packing_list'] ?? null;
                if (!$file) {
                    Notification::make()
                        ->title('Error')
                        ->body('No file uploaded.')
                        ->danger()
                        ->send();
                    return;
                }

                // Get file path - handle both TemporaryUploadedFile and string paths
                if ($file instanceof TemporaryUploadedFile) {
                    $filePath = $file->getRealPath();
                } elseif (is_string($file)) {
                    // File is stored relative to the disk
                    // Try imports directory first (where we configured it)
                    if (Storage::disk('local')->exists('imports/' . basename($file))) {
                        $filePath = Storage::disk('local')->path('imports/' . basename($file));
                    } elseif (Storage::disk('local')->exists($file)) {
                        $filePath = Storage::disk('local')->path($file);
                    } elseif (Storage::exists($file)) {
                        $filePath = Storage::path($file);
                    } else {
                        // Try as absolute path
                        $filePath = storage_path('app/imports/' . basename($file));
                        if (!file_exists($filePath)) {
                            $filePath = storage_path('app/' . $file);
                        }
                        if (!file_exists($filePath)) {
                            $filePath = storage_path('app/private/' . basename($file));
                        }
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
                        ->body('File not found. Please try uploading again.')
                        ->danger()
                        ->send();
                    return;
                }

                // Parse CSV
                $parsedItems = $this->parsePackingListCsv($filePath);

                if (empty($parsedItems)) {
                    Notification::make()
                        ->title('No items found')
                        ->body('The CSV file did not contain any valid items.')
                        ->warning()
                        ->send();
                    return;
                }

                // Add parsed items to existing items
                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                $items = array_merge($items, $parsedItems);
                $record->update(['items' => $items]);

                // Clean up uploaded file
                if (is_string($file)) {
                    Storage::delete($file);
                }

                $this->dispatch('refresh');

                Notification::make()
                    ->title('Packing list uploaded')
                    ->body(count($parsedItems) . ' item(s) added from packing list.')
                    ->success()
                    ->send();
            });
    }

    protected function parsePackingListCsv(string $filePath): array
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
        
        // Normalize header and map columns: Product, Carton, Quantity
        $headerMap = [];
        foreach ($header as $index => $col) {
            $colTrimmed = trim($col);
            $colLower = strtolower($colTrimmed);
            
            if (preg_match('/^product$/i', $colTrimmed)) {
                $headerMap['product'] = $index;
            } elseif (preg_match('/^carton$/i', $colTrimmed) || preg_match('/^ctn#?$/i', $colTrimmed)) {
                $headerMap['carton'] = $index;
            } elseif (preg_match('/^quantity$/i', $colTrimmed) || preg_match('/^qty$/i', $colTrimmed)) {
                $headerMap['quantity'] = $index;
            }
        }
        
        // Fallback: positional mapping if header mapping failed (Product, Carton, Quantity)
        if (empty($headerMap) && count($header) >= 3) {
            $headerMap = [
                'product' => 0,
                'carton' => 1,
                'quantity' => 2,
            ];
        }
        
        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue; // Skip empty or invalid rows
            }
            
            $productName = isset($headerMap['product']) && isset($row[$headerMap['product']]) 
                ? trim($row[$headerMap['product']]) 
                : '';
            
            if (empty($productName)) {
                continue; // Skip rows without product name
            }
            
            // Find matching SockStyle product
            $product = SockStyle::where('name', $productName)->first();
            
            if (!$product) {
                continue; // Skip if product not found
            }
            
            // Parse product name to extract style, color, and packing way
            $name = $product->name;
            $packagingStyle = $product->packaging_style ?? '';
            
            $nameWithoutPackaging = $name;
            if (!empty($packagingStyle)) {
                $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
            }
            
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
            
            $cartonNumber = isset($headerMap['carton']) && isset($row[$headerMap['carton']]) 
                ? trim($row[$headerMap['carton']]) 
                : '';
            
            $quantity = isset($headerMap['quantity']) && isset($row[$headerMap['quantity']]) 
                ? $this->extractQuantity($row[$headerMap['quantity']]) 
                : 1;
            
            $item = [
                'product_id' => $product->id,
                'carton_number' => $cartonNumber,
                'style' => $style,
                'color' => $color,
                'packing_way' => $packagingStyle ?: 'Hook',
                'quantity' => $quantity,
            ];
            
            $items[] = $item;
        }
        
        fclose($handle);
        return $items;
    }

    protected function extractQuantity($value): int
    {
        if (empty($value)) {
            return 1;
        }
        
        // Extract numeric value, handling strings like "100", "100 pcs", etc.
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        // Try to extract number from string
        if (preg_match('/(\d+)/', (string) $value, $matches)) {
            return (int) $matches[1];
        }
        
        return 1;
    }

    public function bulkUpdateQuantityAction(): Actions\Action
    {
        return Actions\Action::make('bulkUpdateQuantity')
            ->label('Bulk Update Quantity')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->form([
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->required()
                    ->default(1),
            ])
            ->action(function (array $data) {
                if (empty($this->selectedItems)) {
                    Notification::make()
                        ->title('No items selected')
                        ->body('Please select items to update.')
                        ->warning()
                        ->send();
                    return;
                }

                if (!$this->record) {
                    return;
                }

                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);

                if (!$record) {
                    return;
                }

                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                $quantity = $data['quantity'] ?? 1;
                $updated = 0;

                foreach ($this->selectedItems as $index) {
                    if (isset($items[$index])) {
                        $items[$index]['quantity'] = $quantity;
                        $updated++;
                    }
                }

                $record->update(['items' => $items]);
                $this->selectedItems = [];
                $this->dispatch('refresh');

                Notification::make()
                    ->title('Quantity updated')
                    ->body("Updated quantity for {$updated} item(s).")
                    ->success()
                    ->send();
            });
    }

    public function bulkUpdateCartonAction(): Actions\Action
    {
        return Actions\Action::make('bulkUpdateCarton')
            ->label('Bulk Update CTN#')
            ->icon('heroicon-o-tag')
            ->color('info')
            ->form(function () {
                if (!$this->record || empty($this->selectedItems)) {
                    return [];
                }

                $items = $this->record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                // Get product names from SockStyle for display
                $viewData = $this->getViewData();
                $itemsWithNames = $viewData['items'] ?? [];

                // Create a repeater for each selected item
                $repeaterItems = [];
                foreach ($this->selectedItems as $index) {
                    if (isset($items[$index])) {
                        $item = $items[$index];
                        
                        // Use product_name from viewData if available, otherwise build from item data
                        $productName = null;
                        foreach ($itemsWithNames as $itemWithName) {
                            if (($itemWithName['index'] ?? null) === $index) {
                                $productName = $itemWithName['product_name'] ?? null;
                                break;
                            }
                        }
                        
                        // Fallback to building name from item data
                        if (!$productName) {
                            $productName = $item['style'] ?? '';
                            if (!empty($item['color'])) {
                                $productName .= ' - ' . $item['color'];
                            }
                            if (!empty($item['packing_way'])) {
                                $productName .= ' - ' . $item['packing_way'];
                            }
                        }
                        
                        $repeaterItems[] = [
                            'index' => $index,
                            'product_name' => $productName,
                            'carton_number' => $item['carton_number'] ?? '',
                        ];
                    }
                }

                return [
                    Forms\Components\Repeater::make('items')
                        ->label('Update Carton Numbers')
                        ->schema([
                            Forms\Components\TextInput::make('product_name')
                                ->label('Product')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('carton_number')
                                ->label('CTN#')
                                ->maxLength(50)
                                ->placeholder('e.g., 1, 2, 3')
                                ->required(),
                            Forms\Components\Hidden::make('index'),
                        ])
                        ->defaultItems(count($repeaterItems))
                        ->default($repeaterItems)
                        ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                        ->collapsible()
                        ->reorderable(false)
                        ->addable(false)
                        ->deletable(false),
                ];
            })
            ->action(function (array $data) {
                if (empty($this->selectedItems)) {
                    Notification::make()
                        ->title('No items selected')
                        ->body('Please select items to update.')
                        ->warning()
                        ->send();
                    return;
                }

                if (!$this->record) {
                    return;
                }

                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);

                if (!$record) {
                    return;
                }

                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                $updated = 0;
                $cartonUpdates = $data['items'] ?? [];

                foreach ($cartonUpdates as $update) {
                    $index = $update['index'] ?? null;
                    $cartonNumber = $update['carton_number'] ?? '';
                    
                    if ($index !== null && isset($items[$index])) {
                        $items[$index]['carton_number'] = $cartonNumber;
                        $updated++;
                    }
                }

                $record->update(['items' => $items]);
                $this->selectedItems = [];
                $this->dispatch('refresh');

                Notification::make()
                    ->title('CTN# updated')
                    ->body("Updated CTN# for {$updated} item(s).")
                    ->success()
                    ->send();
            });
    }

    public function bulkEditItemsAction(): Actions\Action
    {
        return Actions\Action::make('bulkEditItems')
            ->label('Edit Selected')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->form(function () {
                if (!$this->record || empty($this->selectedItems)) {
                    return [];
                }

                $items = $this->record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                // Get product names from SockStyle for display
                $viewData = $this->getViewData();
                $itemsWithNames = $viewData['items'] ?? [];

                // Create a repeater for each selected item
                $repeaterItems = [];
                foreach ($this->selectedItems as $index) {
                    if (isset($items[$index])) {
                        $item = $items[$index];
                        
                        // Use product_name from viewData if available
                        $productName = null;
                        foreach ($itemsWithNames as $itemWithName) {
                            if (($itemWithName['index'] ?? null) === $index) {
                                $productName = $itemWithName['product_name'] ?? null;
                                break;
                            }
                        }
                        
                        // Fallback to building name from item data
                        if (!$productName) {
                            $productName = $item['style'] ?? '';
                            if (!empty($item['color'])) {
                                $productName .= ' - ' . $item['color'];
                            }
                            if (!empty($item['packing_way'])) {
                                $productName .= ' - ' . $item['packing_way'];
                            }
                        }
                        
                        $repeaterItems[] = [
                            'index' => $index,
                            'product_name' => $productName,
                            'product_id' => $item['product_id'] ?? null,
                            'style' => $item['style'] ?? '',
                            'color' => $item['color'] ?? '',
                            'packing_way' => $item['packing_way'] ?? 'Hook',
                            'carton_number' => $item['carton_number'] ?? '',
                            'quantity' => $item['quantity'] ?? 1,
                        ];
                    }
                }

                return [
                    Forms\Components\Repeater::make('items')
                        ->label('Edit Items')
                        ->schema([
                            Forms\Components\TextInput::make('product_name')
                                ->label('Product')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->options(function () {
                                    return SockStyle::orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $product = SockStyle::find($state);
                                        if ($product) {
                                            $name = $product->name;
                                            $packagingStyle = $product->packaging_style ?? '';
                                            
                                            $nameWithoutPackaging = $name;
                                            if (!empty($packagingStyle)) {
                                                $nameWithoutPackaging = preg_replace('/\s*-\s*' . preg_quote($packagingStyle, '/') . '$/i', '', $name);
                                            }
                                            
                                            $parts = explode(' - ', $nameWithoutPackaging);
                                            if (count($parts) >= 2) {
                                                $set('style', trim($parts[0]));
                                                $set('color', trim($parts[1]));
                                            } else {
                                                $set('style', trim($nameWithoutPackaging));
                                                $set('color', '');
                                            }
                                            
                                            if (!empty($packagingStyle)) {
                                                $set('packing_way', $packagingStyle);
                                            }
                                        }
                                    }
                                }),
                            Forms\Components\TextInput::make('carton_number')
                                ->label('CTN#')
                                ->maxLength(50),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required(),
                            Forms\Components\Hidden::make('index'),
                            Forms\Components\Hidden::make('style'),
                            Forms\Components\Hidden::make('color'),
                            Forms\Components\Hidden::make('packing_way'),
                        ])
                        ->defaultItems(count($repeaterItems))
                        ->default($repeaterItems)
                        ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                        ->collapsible()
                        ->reorderable(false)
                        ->addable(false)
                        ->deletable(false),
                ];
            })
            ->action(function (array $data) {
                if (empty($this->selectedItems)) {
                    Notification::make()
                        ->title('No items selected')
                        ->body('Please select items to edit.')
                        ->warning()
                        ->send();
                    return;
                }

                if (!$this->record) {
                    return;
                }

                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);

                if (!$record) {
                    return;
                }

                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                $updated = 0;
                $itemUpdates = $data['items'] ?? [];

                foreach ($itemUpdates as $update) {
                    $index = $update['index'] ?? null;
                    
                    if ($index !== null && isset($items[$index])) {
                        // Remove product_id before saving (as per mutateFormDataBeforeSave logic)
                        $itemData = $update;
                        unset($itemData['product_id']);
                        unset($itemData['product_name']);
                        unset($itemData['index']);
                        
                        $items[$index] = array_merge($items[$index], $itemData);
                        $updated++;
                    }
                }

                $record->update(['items' => $items]);
                $this->selectedItems = [];
                $this->dispatch('refresh');

                Notification::make()
                    ->title('Items updated')
                    ->body("Updated {$updated} item(s).")
                    ->success()
                    ->send();
            });
    }

    public function bulkDeleteItemsAction(): Actions\Action
    {
        return Actions\Action::make('bulkDeleteItems')
            ->label('Delete Selected')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Selected Items')
            ->modalDescription('Are you sure you want to delete the selected items? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete')
            ->action(function () {
                if (empty($this->selectedItems)) {
                    Notification::make()
                        ->title('No items selected')
                        ->body('Please select items to delete.')
                        ->warning()
                        ->send();
                    return;
                }

                if (!$this->record) {
                    return;
                }

                $recordId = $this->record->id;
                $record = \App\Models\IncomingShipment::find($recordId);

                if (!$record) {
                    return;
                }

                $items = $record->items ?? [];
                if (!is_array($items)) {
                    $items = [];
                }

                // Remove selected items (sort indices descending to maintain array integrity)
                $indicesToDelete = $this->selectedItems;
                rsort($indicesToDelete);
                
                foreach ($indicesToDelete as $index) {
                    if (isset($items[$index])) {
                        unset($items[$index]);
                    }
                }

                $items = array_values($items); // Re-index array
                $record->update(['items' => $items]);
                $this->selectedItems = [];
                $this->dispatch('refresh');

                Notification::make()
                    ->title('Items deleted')
                    ->body(count($indicesToDelete) . ' item(s) deleted successfully.')
                    ->success()
                    ->send();
            });
    }

    public function bulkActionsGroup(): Actions\ActionGroup
    {
        return Actions\ActionGroup::make([
            $this->bulkEditItemsAction(),
            $this->bulkUpdateQuantityAction(),
            $this->bulkUpdateCartonAction(),
            $this->bulkDeleteItemsAction(),
        ])
        ->label('Bulk Actions')
        ->icon('heroicon-o-bars-3-bottom-left')
        ->color('gray')
        ->button();
    }

    protected function getActions(): array
    {
        return [
            $this->addProductAction(),
            $this->bulkAddProductsAction(),
            $this->uploadPackingListAction(),
            $this->bulkEditItemsAction(),
            $this->bulkUpdateQuantityAction(),
            $this->bulkUpdateCartonAction(),
            $this->bulkDeleteItemsAction(),
        ];
    }

    public function getViewData(): array
    {
        $items = $this->record->items ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        // Enrich items with product names and ensure product_id is available
        $enrichedItems = [];
        foreach ($items as $index => $item) {
            $productName = 'N/A';
            $productId = $item['product_id'] ?? null;
            
            // If product_id is not set, try to find it by matching style, color, and packing_way
            if (!$productId && isset($item['style']) && isset($item['packing_way'])) {
                $style = $item['style'] ?? '';
                $color = $item['color'] ?? '';
                $packingWay = $item['packing_way'] ?? '';
                
                // Build the expected product name
                $expectedName = $style;
                if (!empty($color)) {
                    $expectedName .= ' - ' . $color;
                }
                if (!empty($packingWay)) {
                    $expectedName .= ' - ' . $packingWay;
                }
                
                // Try to find matching product
                $product = SockStyle::where('name', $expectedName)->first();
                if ($product) {
                    $productId = $product->id;
                }
            }
            
            if ($productId) {
                $product = SockStyle::find($productId);
                $productName = $product?->name ?? 'N/A';
            }
            
            $enrichedItems[] = array_merge($item, [
                'index' => $index,
                'product_name' => $productName,
                'product_id' => $productId,
            ]);
        }

        return [
            'items' => $enrichedItems,
        ];
    }

    public function deleteProduct(int $index): void
    {
        if (!$this->record) {
            return;
        }
        
        $recordId = $this->record->id;
        $record = \App\Models\IncomingShipment::find($recordId);
        
        if (!$record) {
            return;
        }
        
        $items = $record->items ?? [];
        if (!is_array($items)) {
            $items = [];
        }
        
        if (isset($items[$index])) {
            unset($items[$index]);
            $items = array_values($items); // Re-index array
            $record->update(['items' => $items]);
            
            // Dispatch event to refresh the parent component
            $this->dispatch('refresh');
            
            Notification::make()
                ->title('Product deleted')
                ->success()
                ->send();
        }
    }


    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function getMountedFormComponentAction()
    {
        return null;
    }

    public function mountedFormComponentActionShouldOpenModal(): bool
    {
        return false;
    }

    public function mountedFormComponentActionHasForm(): bool
    {
        return false;
    }

    public function getMountedFormComponentActionForm()
    {
        return null;
    }

    public function unmountFormComponentAction(bool $shouldCancelParentActions = true, bool $shouldCloseModal = true): void
    {
        // No-op
    }
}
