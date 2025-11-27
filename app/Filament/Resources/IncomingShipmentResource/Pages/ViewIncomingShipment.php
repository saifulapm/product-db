<?php

namespace App\Filament\Resources\IncomingShipmentResource\Pages;

use App\Filament\Resources\IncomingShipmentResource;
use App\Filament\Resources\IncomingShipmentResource\Widgets\PickListHistoryWidget;
use App\Filament\Resources\IncomingShipmentResource\Widgets\PickListTableWidget;
use App\Filament\Resources\IncomingShipmentResource\Widgets\CartonPickingGuideWidget;
use App\Filament\Resources\IncomingShipmentResource\Widgets\PickListReceivingWidget;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Notifications\Notification;

class ViewIncomingShipment extends ViewRecord
{
    protected static string $resource = IncomingShipmentResource::class;
    
    protected function getFooterWidgets(): array
    {
        return [
            PickListHistoryWidget::class,
            // Widgets temporarily disabled
            // PickListReceivingWidget::class,
            // CartonPickingGuideWidget::class,
            // PickListTableWidget::class,
        ];
    }

    public $pickLists = []; // Array of pick lists: [['name' => '...', 'items' => [...], 'picked_items' => [...], 'filename' => '...'], ...]
    public $selectedPickListItems = []; // Track selected items: ['pickListIndex_itemIndex' => true]
    
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Load pick lists from database
        $this->loadPickLists();
    }
    
    protected function loadPickLists(): void
    {
        // Reload record to get latest data
        $this->record->refresh();
        $this->pickLists = $this->record->pick_lists ?? [];
        if (!is_array($this->pickLists)) {
            $this->pickLists = [];
        }
    }
    
    protected function savePickLists(): void
    {
        $this->record->pick_lists = $this->pickLists;
        $this->record->save();
        
        \Log::info('Saved pick lists to database', [
            'shipment_id' => $this->record->id,
            'pick_lists_count' => count($this->pickLists),
            'pick_lists' => $this->pickLists,
        ]);
        
        // Reload to ensure consistency
        $this->loadPickLists();
        
        // Force refresh the record
        $this->record->refresh();
    }
    
    public function markItemAsPicked(int $pickListIndex, int $itemIndex, int $shipmentItemIndex, int $quantityToPick): void
    {
        if (!isset($this->pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Error')
                ->body('Pick list not found.')
                ->danger()
                ->send();
            return;
        }
        
        $pickList = &$this->pickLists[$pickListIndex];
        
        // Initialize picked_items if not exists
        if (!isset($pickList['picked_items']) || !is_array($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        // Add or update picked item
        $found = false;
        foreach ($pickList['picked_items'] as &$picked) {
            if (($picked['item_index'] ?? null) === $itemIndex && 
                ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex) {
                $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityToPick;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $pickList['picked_items'][] = [
                'item_index' => $itemIndex,
                'shipment_item_index' => $shipmentItemIndex,
                'quantity_picked' => $quantityToPick,
                'picked_at' => now()->toDateTimeString(),
            ];
        }
        
                    Notification::make()
                        ->title('Item marked as picked')
                        ->body('Quantity ' . number_format($quantityToPick) . ' marked as picked.')
                        ->success()
                        ->send();
        
        // Save pick lists to database
        $this->savePickLists();
        
        // Reload pick lists to ensure fresh data
        $this->loadPickLists();
        
        // Refresh the component to update available quantities
        $this->dispatch('pick-list-updated');
        $this->dispatch('$refresh');
        
        // Force reload the page to update the packing list table with new available quantities
        redirect()->to(\App\Filament\Resources\IncomingShipmentResource::getUrl('view', ['record' => $this->record->id]));
    }
    
    public function bulkMarkItemsAsPicked(int $pickListIndex, array $itemData): void
    {
        if (!isset($this->pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Error')
                ->body('Pick list not found.')
                ->danger()
                ->send();
            return;
        }
        
        $pickList = &$this->pickLists[$pickListIndex];
        $pickListItems = $pickList['items'] ?? [];
        
        if (empty($pickListItems) || !is_array($pickListItems)) {
            Notification::make()
                ->title('Error')
                ->body('No items in pick list.')
                ->danger()
                ->send();
            return;
        }
        
        // Initialize picked_items if not exists
        if (!isset($pickList['picked_items']) || !is_array($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        $markedCount = 0;
        $record = $this->record;
        
        foreach ($itemData as $data) {
            $itemIndex = $data['itemIndex'] ?? null;
            if ($itemIndex === null || !isset($pickListItems[$itemIndex])) {
                continue;
            }
            
            $orderItem = $pickListItems[$itemIndex];
            
            // Parse item details
            if (isset($orderItem['description'])) {
                $parsed = \App\Models\Order::parseOrderDescription($orderItem['description']);
                $style = $parsed['style'] ?? '';
                $color = $parsed['color'] ?? '';
                $packingWay = $parsed['packing_way'] ?? 'hook';
                $quantityNeeded = $orderItem['quantity_required'] ?? $orderItem['quantity'] ?? 0;
            } else {
                $style = $orderItem['style'] ?? '';
                $color = $orderItem['color'] ?? '';
                $packingWay = $orderItem['packing_way'] ?? 'hook';
                $quantityNeeded = $orderItem['quantity'] ?? 0;
            }
            
            // Get picked quantity for this item
            $pickedByItemIndex = [];
            foreach ($pickList['picked_items'] as $picked) {
                $idx = $picked['item_index'] ?? null;
                if ($idx === $itemIndex) {
                    if (!isset($pickedByItemIndex[$idx])) {
                        $pickedByItemIndex[$idx] = 0;
                    }
                    $pickedByItemIndex[$idx] += $picked['quantity_picked'] ?? 0;
                }
            }
            
            $quantityPicked = $pickedByItemIndex[$itemIndex] ?? 0;
            $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
            
            if ($quantityRemaining <= 0) {
                continue; // Already fully picked
            }
            
            // Find matching shipment item index
            $matchingShipmentIndex = null;
            if (!empty($record->items) && is_array($record->items)) {
                $normalizedStyle = strtolower(trim($style));
                $normalizedColor = trim(strtolower(trim($color)), ' -');
                $normalizedPackingWay = strtolower(trim($packingWay));
                
                if (strpos($normalizedPackingWay, 'hook') !== false) {
                    $normalizedPackingWay = 'hook';
                } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                    $normalizedPackingWay = 'sleeve wrap';
                }
                
                foreach ($record->items as $shipIndex => $shipItem) {
                    $shipStyle = strtolower(trim($shipItem['style'] ?? ''));
                    $shipColor = trim(strtolower(trim($shipItem['color'] ?? '')), ' -');
                    $shipPackingWay = strtolower(trim($shipItem['packing_way'] ?? ''));
                    
                    if (strpos($shipPackingWay, 'hook') !== false) {
                        $shipPackingWay = 'hook';
                    } elseif (strpos($shipPackingWay, 'sleeve') !== false || strpos($shipPackingWay, 'wrap') !== false) {
                        $shipPackingWay = 'sleeve wrap';
                    }
                    
                    $styleMatch = $shipStyle === $normalizedStyle || 
                                 (strpos($shipStyle, $normalizedStyle) !== false || strpos($normalizedStyle, $shipStyle) !== false);
                    $colorMatch = $shipColor === $normalizedColor || 
                                 (strpos($shipColor, $normalizedColor) !== false || strpos($normalizedColor, $shipColor) !== false);
                    $packingMatch = $shipPackingWay === $normalizedPackingWay;
                    
                    if ($styleMatch && $colorMatch && $packingMatch) {
                        $matchingShipmentIndex = $shipIndex;
                        break;
                    }
                }
            }
            
            if ($matchingShipmentIndex !== null) {
                // Add or update picked item
                $found = false;
                foreach ($pickList['picked_items'] as &$picked) {
                    if (($picked['item_index'] ?? null) === $itemIndex && 
                        ($picked['shipment_item_index'] ?? null) === $matchingShipmentIndex) {
                        $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityRemaining;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $pickList['picked_items'][] = [
                        'item_index' => $itemIndex,
                        'shipment_item_index' => $matchingShipmentIndex,
                        'quantity_picked' => $quantityRemaining,
                        'picked_at' => now()->toDateTimeString(),
                    ];
                }
                
                $markedCount++;
            }
        }
        
        if ($markedCount > 0) {
            // Save pick lists to database
            $this->savePickLists();
            
            // Reload pick lists to ensure fresh data
            $this->loadPickLists();
            
            // Refresh the component to update available quantities
            $this->dispatch('pick-list-updated');
            $this->dispatch('$refresh');
            
            // Force reload the page to update the packing list table with new available quantities
            redirect()->to(\App\Filament\Resources\IncomingShipmentResource::getUrl('view', ['record' => $this->record->id]));
            
            // Clear selections
            foreach ($itemData as $data) {
                $itemIndex = $data['itemIndex'] ?? null;
                if ($itemIndex !== null) {
                    unset($this->selectedPickListItems[$pickListIndex . '_' . $itemIndex]);
                }
            }
            
            Notification::make()
                ->title('Items marked as picked')
                ->body($markedCount . ' item(s) marked as picked.')
                ->success()
                ->send();
            
            $this->dispatch('pick-list-updated');
        } else {
            Notification::make()
                ->title('No items marked')
                ->body('Could not mark items as picked. Please check that items match shipment items.')
                ->warning()
                ->send();
        }
    }
    
    public function bulkDeletePickListItems(int $pickListIndex, array $itemIndices): void
    {
        if (!isset($this->pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Error')
                ->body('Pick list not found.')
                ->danger()
                ->send();
            return;
        }
        
        $pickList = &$this->pickLists[$pickListIndex];
        $pickListItems = $pickList['items'] ?? [];
        
        if (empty($pickListItems) || !is_array($pickListItems)) {
            Notification::make()
                ->title('Error')
                ->body('No items in pick list.')
                ->danger()
                ->send();
            return;
        }
        
        // Sort indices descending to safely delete from array
        rsort($itemIndices);
        
        $deletedCount = 0;
        foreach ($itemIndices as $itemIndex) {
            if (isset($pickListItems[$itemIndex])) {
                // Remove item from items array
                unset($pickListItems[$itemIndex]);
                
                // Remove any picked items for this item index
                if (isset($pickList['picked_items']) && is_array($pickList['picked_items'])) {
                    $pickList['picked_items'] = array_values(array_filter($pickList['picked_items'], function ($picked) use ($itemIndex) {
                        return ($picked['item_index'] ?? null) !== $itemIndex;
                    }));
                }
                
                $deletedCount++;
            }
        }
        
        // Re-index array and update
        $pickList['items'] = array_values($pickListItems);
        
        // Clear selections
        foreach ($itemIndices as $itemIndex) {
            unset($this->selectedPickListItems[$pickListIndex . '_' . $itemIndex]);
        }
        
        if ($deletedCount > 0) {
            // Save pick lists to database
            $this->savePickLists();
            
            Notification::make()
                ->title('Items deleted')
                ->body($deletedCount . ' item(s) deleted from pick list.')
                ->success()
                ->send();
            
            $this->dispatch('pick-list-updated');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_upload_pick_lists')
                ->label('Bulk Upload Pick Lists')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\Repeater::make('pick_lists')
                        ->label('Pick Lists')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Pick List Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Order BDR1399')
                                ->extraAttributes(fn ($livewire, $statePath) => [
                                    'id' => 'bulk_upload_pick_list_name_' . md5($statePath ?? ''),
                                    'name' => 'name'
                                ]),
                            Forms\Components\FileUpload::make('file')
                                ->label('Pick List File')
                                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'])
                                ->required()
                                ->disk('local')
                                ->directory('pick-lists')
                                ->visibility('private')
                                ->multiple(false)
                                ->maxFiles(1)
                                ->downloadable(false)
                                ->openable(false)
                                ->previewable(false)
                                ->extraAttributes(fn ($livewire, $statePath) => [
                                    'id' => 'bulk_upload_pick_list_file_' . md5($statePath ?? ''),
                                    'name' => 'file'
                                ]),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Add Another Pick List')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->action(function (array $data) {
                    $uploadedCount = 0;
                    $errors = [];
                    
                    foreach ($data['pick_lists'] ?? [] as $pickListData) {
                        try {
                            $file = is_array($pickListData['file']) ? $pickListData['file'][0] : $pickListData['file'];
                            $filePath = null;
                            $fileToDelete = null;
                            
                            if ($file instanceof TemporaryUploadedFile) {
                                $storedPath = $file->storeAs('pick-lists', $file->getClientOriginalName(), 'local');
                                $filePath = Storage::disk('local')->path($storedPath);
                                $fileToDelete = $storedPath;
                            } elseif (is_string($file)) {
                                if (Storage::disk('local')->exists($file)) {
                                    $filePath = Storage::disk('local')->path($file);
                                    $fileToDelete = $file;
                                }
                            }
                            
                            if (!$filePath || !file_exists($filePath)) {
                                $errors[] = ($pickListData['name'] ?? 'Unknown') . ': File not found';
                                continue;
                            }
                            
                            $parsedItems = $this->parsePickListFile($filePath);
                            
                            if (empty($parsedItems)) {
                                $errors[] = ($pickListData['name'] ?? 'Unknown') . ': No items found in file';
                                if ($fileToDelete && is_string($fileToDelete)) {
                                    Storage::disk('local')->delete($fileToDelete);
                                }
                                continue;
                            }
                            
                            if (!is_array($this->pickLists)) {
                                $this->pickLists = [];
                            }
                            
                            $pickListName = $pickListData['name'] ?? 'Pick List ' . (count($this->pickLists) + 1);
                            $fileName = is_string($file) ? basename($file) : ($file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : 'pick-list');
                            
                            $this->pickLists[] = [
                                'id' => uniqid('pl_', true),
                                'name' => $pickListName,
                                'filename' => $fileName,
                                'items' => $parsedItems,
                                'picked_items' => [],
                                'uploaded_at' => now()->toDateTimeString(),
                                'status' => 'pending', // pending, in_progress, completed
                                'order_id' => null, // Link to Order model if needed
                            ];
                            
                            if ($fileToDelete && is_string($fileToDelete)) {
                                Storage::disk('local')->delete($fileToDelete);
                            }
                            
                            $uploadedCount++;
                        } catch (\Exception $e) {
                            $errors[] = ($pickListData['name'] ?? 'Unknown') . ': ' . $e->getMessage();
                        }
                    }
                    
                    if ($uploadedCount > 0) {
                        // Save pick lists to database (this also reloads pick lists)
                        $this->savePickLists();
                        
                        $message = $uploadedCount . ' pick list(s) uploaded successfully';
                        if (!empty($errors)) {
                            $message .= '. ' . count($errors) . ' error(s): ' . implode(', ', $errors);
                        }
                        
                        Notification::make()
                            ->title('Bulk upload complete')
                            ->body($message)
                            ->success()
                            ->send();
                        
                        // Force Livewire refresh to show the picking guide
                        $this->dispatch('pick-list-updated');
                        $this->dispatch('$refresh');
                    } else {
                        Notification::make()
                            ->title('Upload failed')
                            ->body('No pick lists were uploaded. Errors: ' . implode(', ', $errors))
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('upload_pick_list')
                ->label('Upload Pick List')
                ->icon('heroicon-o-document-arrow-up')
                ->color('info')
                ->form([
                    Forms\Components\TextInput::make('pick_list_name')
                        ->label('Pick List Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., Order BDR1399, Client ABC Order, etc.')
                        ->helperText('Give this pick list a name to identify it (e.g., order number or client name)')
                        ->extraAttributes(['id' => 'upload_pick_list_name', 'name' => 'pick_list_name']),
                    Forms\Components\FileUpload::make('pick_list_file')
                        ->label('Pick List File (CSV, Excel, or PDF)')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'])
                        ->required()
                        ->helperText('Upload order/pick list file. The system will match items against this shipment and show which cartons to pick from.')
                        ->disk('local')
                        ->directory('pick-lists')
                        ->visibility('private')
                        ->multiple(false)
                        ->maxFiles(1)
                        ->downloadable(false)
                        ->openable(false)
                        ->previewable(false)
                        ->extraAttributes(['id' => 'upload_pick_list_file', 'name' => 'pick_list_file']),
                ])
                ->action(function (array $data) {
                    // Safety check - ensure we have the required field
                    if (!isset($data['pick_list_file'])) {
                        Notification::make()
                            ->title('Missing file')
                            ->body('Please select a pick list file to upload.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    $filePath = null;
                    $fileToDelete = null;
                    
                    $file = is_array($data['pick_list_file']) ? $data['pick_list_file'][0] : $data['pick_list_file'];
                    
                    if ($file instanceof TemporaryUploadedFile) {
                        $storedPath = $file->storeAs('pick-lists', $file->getClientOriginalName(), 'local');
                        $filePath = Storage::disk('local')->path($storedPath);
                        $fileToDelete = $storedPath;
                    } elseif (is_string($file)) {
                        if (Storage::disk('local')->exists($file)) {
                            $filePath = Storage::disk('local')->path($file);
                            $fileToDelete = $file;
                        }
                    }
                    
                    if (!$filePath || !file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found')
                            ->body('Could not locate the uploaded file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    try {
                        $parsedItems = $this->parsePickListFile($filePath);
                        
                        \Log::info('Pick list parsing result', [
                            'file_path' => $filePath,
                            'file_exists' => file_exists($filePath),
                            'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
                            'items_count' => count($parsedItems),
                            'items' => $parsedItems,
                        ]);
                        
                        if (empty($parsedItems)) {
                            // Try to get more info about why parsing failed
                            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                            $errorMsg = 'Could not parse any items from the ' . strtoupper($extension) . ' file. ';
                            
                            if ($extension === 'pdf') {
                                // Try to read PDF text to see what we got
                                try {
                                    $parser = new \Smalot\PdfParser\Parser();
                                    $pdf = $parser->parseFile($filePath);
                                    $text = $pdf->getText();
                                    $textPreview = substr($text, 0, 500);
                                    \Log::info('PDF text preview', ['text' => $textPreview]);
                                    $errorMsg .= 'PDF contains text but no items were extracted. Check logs for PDF content preview.';
                                } catch (\Exception $e) {
                                    $errorMsg .= 'Error reading PDF: ' . $e->getMessage();
                                    \Log::error('PDF parsing error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                                }
                            }
                            
                            Notification::make()
                                ->title('No items found')
                                ->body($errorMsg)
                                ->warning()
                                ->send();
                            
                            if ($fileToDelete && is_string($fileToDelete)) {
                                Storage::disk('local')->delete($fileToDelete);
                            }
                            return;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error parsing pick list file', [
                            'file_path' => $filePath,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        
                        Notification::make()
                            ->title('Parsing error')
                            ->body('Error parsing pick list file: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        
                        if ($fileToDelete && is_string($fileToDelete)) {
                            Storage::disk('local')->delete($fileToDelete);
                        }
                        return;
                    }
                    
                    // Initialize pickLists array if empty
                    if (!is_array($this->pickLists)) {
                        $this->pickLists = [];
                    }
                    
                    // Add new pick list to the array
                    $pickListName = $data['pick_list_name'] ?? 'Pick List ' . (count($this->pickLists) + 1);
                    $fileName = is_string($file) ? basename($file) : ($file instanceof TemporaryUploadedFile ? $file->getClientOriginalName() : 'pick-list');
                    
                    $this->pickLists[] = [
                        'id' => uniqid('pl_', true),
                        'name' => $pickListName,
                        'filename' => $fileName,
                        'items' => $parsedItems,
                        'picked_items' => [], // Track which items have been picked: [['shipment_item_index' => X, 'quantity_picked' => Y, 'item_index' => Z], ...]
                        'uploaded_at' => now()->toDateTimeString(),
                        'status' => 'pending', // pending, in_progress, completed
                        'order_id' => null, // Link to Order model if needed
                    ];
                    
                    // Clean up file
                    if ($fileToDelete && is_string($fileToDelete)) {
                        Storage::disk('local')->delete($fileToDelete);
                    }
                    
                    Notification::make()
                        ->title('Pick list uploaded')
                        ->body('"' . $pickListName . '" added with ' . count($parsedItems) . ' items. See picking guide below.')
                        ->success()
                        ->send();
                    
                    // Save pick lists to database (this also reloads pick lists via loadPickLists)
                    $this->savePickLists();
                    
                    // Force Livewire refresh to show the picking guide
                    // Use multiple events to ensure widgets refresh
                    $this->dispatch('pick-list-updated');
                    $this->dispatch('$refresh');
                    $this->dispatch('refresh-widgets');
                    
                    // Force reload the page to ensure widgets refresh
                    return redirect()->to(\App\Filament\Resources\IncomingShipmentResource::getUrl('view', ['record' => $this->record->id]));
                }),
            Actions\Action::make('mark_pick_list_complete')
                ->label('Mark Pick List as Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('pick_list_index')
                        ->label('Pick List')
                        ->options(function () {
                            $options = [];
                            foreach ($this->pickLists as $index => $pickList) {
                                $name = $pickList['name'] ?? 'Pick List ' . ($index + 1);
                                $status = $pickList['status'] ?? 'pending';
                                if ($status !== 'completed') {
                                    $options[$index] = $name;
                                }
                            }
                            return $options;
                        })
                        ->required()
                        ->helperText('Select a pick list to mark as complete'),
                ])
                ->action(function (array $data) {
                    $pickListIndex = $data['pick_list_index'] ?? null;
                    
                    if ($pickListIndex === null || !isset($this->pickLists[$pickListIndex])) {
                        Notification::make()
                            ->title('Error')
                            ->body('Pick list not found.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    $this->pickLists[$pickListIndex]['status'] = 'completed';
                    $this->savePickLists();
                    
                    Notification::make()
                        ->title('Pick list marked as complete')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !empty($this->pickLists) && is_array($this->pickLists) && count($this->pickLists) > 0),
            Actions\Action::make('create_order')
                ->label('Create Order from Shipment')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->url(fn () => \App\Filament\Resources\OrderResource::getUrl('create', [
                    'incoming_shipment_id' => $this->record->id,
                ]))
                ->visible(fn () => $this->record->status === 'received'),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Shipment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Shipment Name')
                            ->size('lg')
                            ->weight('bold')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('tracking_number')
                            ->label('Tracking Number'),
                        Infolists\Components\TextEntry::make('carrier')
                            ->label('Carrier'),
                        Infolists\Components\TextEntry::make('supplier')
                            ->label('Supplier'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'in_transit' => 'info',
                                'received' => 'success',
                                'delayed' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pending',
                                'in_transit' => 'In Transit',
                                'received' => 'Received',
                                'delayed' => 'Delayed',
                                'cancelled' => 'Cancelled',
                                default => $state,
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('expected_date')
                            ->label('Expected Date')
                            ->date('M d, Y'),
                        Infolists\Components\TextEntry::make('received_date')
                            ->label('Received Date')
                            ->date('M d, Y'),
                    ])
                    ->columns(2),
                
                // Pick Lists Overview Section - Shows all pick lists and their status
                Infolists\Components\Section::make('Pick Lists Overview')
                    ->description('Manage multiple pick lists that split this bulk shipment into individual orders.')
                    ->columnSpanFull()
                    ->visible(fn () => !empty($this->pickLists) && is_array($this->pickLists) && count($this->pickLists) > 0)
                    ->schema([
                        Infolists\Components\TextEntry::make('pick_lists_overview')
                            ->label('')
                            ->html()
                            ->formatStateUsing(function () {
                                if (empty($this->pickLists) || !is_array($this->pickLists)) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 px-6">No pick lists uploaded yet.</p>');
                                }
                                
                                $html = '<div class="space-y-4">';
                                
                                foreach ($this->pickLists as $pickListIndex => $pickList) {
                                    $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
                                    $fileName = $pickList['filename'] ?? 'Unknown';
                                    $uploadedAt = $pickList['uploaded_at'] ?? '';
                                    $status = $pickList['status'] ?? 'pending'; // pending, in_progress, completed
                                    $orderId = $pickList['order_id'] ?? null;
                                    
                                    // Calculate progress
                                    $items = $pickList['items'] ?? [];
                                    $pickedItems = $pickList['picked_items'] ?? [];
                                    
                                    $totalNeeded = 0;
                                    foreach ($items as $item) {
                                        $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
                                    }
                                    
                                    $totalPicked = 0;
                                    foreach ($pickedItems as $picked) {
                                        $totalPicked += $picked['quantity_picked'] ?? 0;
                                    }
                                    
                                    $remaining = max(0, $totalNeeded - $totalPicked);
                                    $progressPercent = $totalNeeded > 0 ? round(($totalPicked / $totalNeeded) * 100) : 0;
                                    
                                    // Format uploaded date
                                    $uploadedAtFormatted = '';
                                    if ($uploadedAt) {
                                        try {
                                            $uploadedAtFormatted = \Carbon\Carbon::parse($uploadedAt)->format('M d, Y g:i A');
                                        } catch (\Exception $e) {
                                            $uploadedAtFormatted = $uploadedAt;
                                        }
                                    }
                                    
                                    // Status badge color
                                    $statusColor = match($status) {
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                                    };
                                    
                                    $statusLabel = match($status) {
                                        'completed' => 'Completed',
                                        'in_progress' => 'In Progress',
                                        default => 'Pending',
                                    };
                                    
                                    $pickListUrl = IncomingShipmentResource::getUrl('view-pick-list', [
                                        'shipmentId' => $this->record->id,
                                        'pickListIndex' => $pickListIndex,
                                    ]);
                                    
                                    $html .= '
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <a href="' . $pickListUrl . '" class="text-lg font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                                            ' . htmlspecialchars($pickListName) . '
                                                            <svg class="inline-block h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                        <span class="px-2 py-1 text-xs font-medium rounded ' . $statusColor . '">
                                                            ' . htmlspecialchars($statusLabel) . '
                                                        </span>
                                                    </div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                        <div>File: ' . htmlspecialchars($fileName) . '</div>
                                                        ' . ($uploadedAtFormatted ? '<div>Uploaded: ' . htmlspecialchars($uploadedAtFormatted) . '</div>' : '') . '
                                                        ' . ($orderId ? '<div>Linked Order: #' . htmlspecialchars($orderId) . '</div>' : '') . '
                                                    </div>
                                                    <div class="flex items-center gap-4 text-sm mb-3">
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong>' . number_format(count($items)) . '</strong> items
                                                        </span>
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong>' . number_format($totalNeeded) . '</strong> pcs needed
                                                        </span>
                                                        <span class="text-green-600 dark:text-green-400">
                                                            <strong>' . number_format($totalPicked) . '</strong> pcs picked
                                                        </span>
                                                        <span class="text-orange-600 dark:text-orange-400">
                                                            <strong>' . number_format($remaining) . '</strong> pcs remaining
                                                        </span>
                                                    </div>
                                                    ' . ($totalNeeded > 0 ? '
                                                    <div class="mt-3">
                                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                            <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: ' . $progressPercent . '%"></div>
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . $progressPercent . '% complete</div>
                                                    </div>
                                                    ' : '') . '
                                                </div>
                                                <div class="ml-4 flex items-center gap-2">
                                                    <a href="' . $pickListUrl . '" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                                                        View Pick List
                                                        <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    ';
                                }
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Packing List Items - Available Stock')
                    ->description('Tracks all inventory: Original quantities, order allocations (with box breakdown), allocated quantities, picked quantities, and remaining available stock. Use the search box to filter by any column, or filter by Order Number to see only items for specific orders. Updates automatically when items are marked as picked.')
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\TextEntry::make('items_display')
                            ->label('')
                            ->extraAttributes([
                                'style' => 'margin-left: -1.5rem !important; margin-right: -1.5rem !important; width: calc(100% + 3rem) !important; max-width: calc(100% + 3rem) !important; padding-left: 0 !important; padding-right: 0 !important;'
                            ])
                            ->getStateUsing(function ($record) {
                                // Return a placeholder string to prevent array-to-string conversion
                                // The actual rendering happens in formatStateUsing
                                return 'items';
                            })
                            ->formatStateUsing(function ($state, $record) {
                                        // Get the actual items array from the record
                                        $items = $record->items ?? [];
                                        
                                        if (empty($items) || !is_array($items)) {
                                            return new \Illuminate\Support\HtmlString('<p class="text-gray-500 px-6">No items in this shipment.</p>');
                                        }
                                        
                                        // Get available quantities by carton (including picked items from pick lists)
                                        $availableQuantities = $record->getAvailableQuantitiesByCarton($this->pickLists ?? []);
                                        
                                        // Get order allocations per item
                                        $orderAllocations = $record->getOrderAllocationsByItem($this->pickLists ?? []);
                                        
                                        // Get box-by-box allocations for orders (auto-calculates multi-box splits)
                                        $boxAllocations = $record->getBoxAllocationsForOrders($this->pickLists ?? []);
                                
                                // Break out of Filament's section padding and ensure full width
                                $html = '<div style="width: 100%; margin: 0; padding: 0; overflow-x: auto;">
                                    <div class="mb-4 px-6 space-y-3">
                                        <div>
                                            <input 
                                                type="text" 
                                                id="packing-list-search-input"
                                                placeholder="Search by CTN#, Style, Color, Packing Way, or Quantity..." 
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                style="padding: 0.625rem 1rem; font-size: 0.875rem;"
                                            />
                                        </div>
                                        <div>
                                            <input 
                                                type="text" 
                                                id="packing-list-order-filter-input"
                                                placeholder="Filter by Order Number (e.g., BDR1399)..." 
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                style="padding: 0.625rem 1rem; font-size: 0.875rem;"
                                            />
                                        </div>
                                    </div>
                                    <table id="packing-list-table" class="w-full divide-y divide-gray-200 dark:divide-gray-700 border-x-0 border-y border-gray-200 dark:border-gray-700" style="width: 100%; table-layout: fixed; margin: 0;">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th style="width: 7%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">CTN#</th>
                                            <th style="width: 18%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Style</th>
                                            <th style="width: 18%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Color</th>
                                            <th style="width: 12%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Packing Way</th>
                                            <th style="width: 8%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Original Qty</th>
                                            <th style="width: 15%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Order Allocations</th>
                                            <th style="width: 8%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Allocated</th>
                                            <th style="width: 7%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Picked</th>
                                            <th style="width: 7%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Available</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
                                
                                $totalOriginal = 0;
                                $totalAllocated = 0;
                                $totalPicked = 0;
                                $totalAvailable = 0;
                                
                                foreach ($availableQuantities as $item) {
                                    $itemIndex = $item['index'];
                                    $carton = $item['carton_number'] ?: '—';
                                    $style = $item['style'] ?: '—';
                                    $color = $item['color'] ?: '—';
                                    $packingWay = $item['packing_way'] ?: '—';
                                    $originalQty = $item['original_quantity'];
                                    $allocatedQty = $item['allocated_quantity'];
                                    $pickedQty = $item['picked_quantity'] ?? 0;
                                    $availableQty = $item['available_quantity'];
                                    
                                    $totalOriginal += $originalQty;
                                    $totalAllocated += $allocatedQty;
                                    $totalPicked += $pickedQty;
                                    $totalAvailable += $availableQty;
                                    
                                    // Get order allocations for this item with box breakdown
                                    $itemAllocations = $orderAllocations[$itemIndex] ?? [];
                                    $allocationsHtml = '';
                                    
                                    if (!empty($itemAllocations)) {
                                        // Sort allocations by order number (alphabetically/numerically)
                                        usort($itemAllocations, function($a, $b) {
                                            return strcmp($a['order_number'], $b['order_number']);
                                        });
                                        
                                        $allocationsList = [];
                                        
                                        foreach ($itemAllocations as $allocation) {
                                            $orderNum = htmlspecialchars($allocation['order_number']);
                                            $totalQty = $allocation['quantity'];
                                            
                                            // Find box breakdown for this order/product combination
                                            // Match by item index (most accurate)
                                            $boxBreakdown = [];
                                            if (isset($boxAllocations[$orderNum])) {
                                                foreach ($boxAllocations[$orderNum] as $boxAlloc) {
                                                    if ($boxAlloc['item_index'] == $itemIndex) {
                                                        $boxBreakdown[] = [
                                                            'carton' => $boxAlloc['carton'],
                                                            'quantity' => $boxAlloc['quantity'],
                                                        ];
                                                    }
                                                }
                                            }
                                            
                                            // Sort boxes by carton number (ascending)
                                            usort($boxBreakdown, function($a, $b) {
                                                return (int)$a['carton'] <=> (int)$b['carton'];
                                            });
                                            
                                            // If we have box breakdown, show PRIMARY box prominently
                                            if (!empty($boxBreakdown)) {
                                                $primaryBox = $boxBreakdown[0];
                                                $primaryBoxNum = htmlspecialchars($primaryBox['carton']);
                                                $primaryBoxQty = number_format($primaryBox['quantity']);
                                                
                                                // Show additional boxes if needed
                                                $additionalBoxes = [];
                                                if (count($boxBreakdown) > 1) {
                                                    for ($i = 1; $i < count($boxBreakdown); $i++) {
                                                        $additionalBoxes[] = 'Box ' . htmlspecialchars($boxBreakdown[$i]['carton']) . ': ' . number_format($boxBreakdown[$i]['quantity']);
                                                    }
                                                }
                                                
                                                $boxInfo = '';
                                                if (!empty($additionalBoxes)) {
                                                    $boxInfo = '<span class="text-xs opacity-75 block mt-0.5">Then: ' . implode(', ', $additionalBoxes) . '</span>';
                                                }
                                                
                                                $allocationsList[] = '<div class="inline-flex flex-col gap-0.5 px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 border-l-2 border-blue-500">
                                                    <span class="font-semibold">' . $orderNum . ' × ' . number_format($totalQty) . '</span>
                                                    <span class="text-xs font-bold text-blue-900 dark:text-blue-100">→ Pick from Box ' . $primaryBoxNum . ' (' . $primaryBoxQty . ' pcs)</span>
                                                    ' . $boxInfo . '
                                                </div>';
                                            } else {
                                                $allocationsList[] = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    <span class="font-semibold">' . $orderNum . '</span>
                                                    <span>×</span>
                                                    <span>' . number_format($totalQty) . '</span>
                                                </span>';
                                            }
                                        }
                                        
                                        $allocationsHtml = '<div class="flex flex-wrap gap-1">' . implode('', $allocationsList) . '</div>';
                                    } else {
                                        $allocationsHtml = '<span class="text-gray-400 dark:text-gray-500 text-xs">—</span>';
                                    }
                                    
                                    // Calculate tracking status
                                    $isFullyPicked = $availableQty === 0 && $originalQty > 0;
                                    $isPartiallyPicked = $pickedQty > 0 && $availableQty > 0;
                                    $hasAllocations = $allocatedQty > 0;
                                    
                                    // Color code: red if empty, yellow if low, green if available
                                    $availableClass = $availableQty === 0 
                                        ? 'text-red-600 dark:text-red-400 font-semibold' 
                                        : ($availableQty < $originalQty * 0.5 
                                            ? 'text-yellow-600 dark:text-yellow-400' 
                                            : 'text-green-600 dark:text-green-400');
                                    
                                    $pickedClass = $pickedQty > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-500 dark:text-gray-400';
                                    
                                    // Add visual indicator for tracking status
                                    $rowClass = '';
                                    if ($isFullyPicked) {
                                        $rowClass = 'bg-green-50 dark:bg-green-900/20';
                                    } elseif ($isPartiallyPicked) {
                                        $rowClass = 'bg-blue-50 dark:bg-blue-900/20';
                                    }
                                    
                                    // Show tracking breakdown tooltip
                                    $trackingInfo = [];
                                    if ($originalQty > 0) {
                                        $trackingInfo[] = 'Original: ' . number_format($originalQty);
                                    }
                                    if ($allocatedQty > 0) {
                                        $trackingInfo[] = 'Allocated: ' . number_format($allocatedQty);
                                    }
                                    if ($pickedQty > 0) {
                                        $trackingInfo[] = 'Picked: ' . number_format($pickedQty);
                                    }
                                    if ($availableQty >= 0) {
                                        $trackingInfo[] = 'Available: ' . number_format($availableQty);
                                    }
                                    $trackingTooltip = htmlspecialchars(implode(' | ', $trackingInfo));
                                    
                                    $html .= '<tr class="packing-list-row hover:bg-gray-50 dark:hover:bg-gray-700/50 ' . $rowClass . '" title="' . $trackingTooltip . '">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 font-medium">' . htmlspecialchars($carton) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($style) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($color) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . htmlspecialchars($packingWay) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right border-r border-gray-200 dark:border-gray-700 font-medium">' . number_format($originalQty) . '</td>
                                        <td class="px-4 py-3 text-sm border-r border-gray-200 dark:border-gray-700">' . $allocationsHtml . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right border-r border-gray-200 dark:border-gray-700">' . number_format($allocatedQty) . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium border-r border-gray-200 dark:border-gray-700 ' . $pickedClass . '">' . number_format($pickedQty) . ($isFullyPicked ? ' ✓' : '') . '</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium ' . $availableClass . '">' . number_format($availableQty) . '</td>
                                    </tr>';
                                }
                                
                                $html .= '</tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">Totals:</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">' . number_format($totalOriginal) . '</td>
                                            <td class="px-4 py-3 text-sm border-r border-gray-200 dark:border-gray-700">—</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700">' . number_format($totalAllocated) . '</td>
                                            <td class="px-4 py-3 text-right text-sm text-blue-600 dark:text-blue-400 border-r border-gray-200 dark:border-gray-700">' . number_format($totalPicked) . '</td>
                                            <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">' . number_format($totalAvailable) . '</td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <script>
                                    (function() {
                                        function initPackingListFilters() {
                                            const searchInput = document.getElementById("packing-list-search-input");
                                            const orderFilterInput = document.getElementById("packing-list-order-filter-input");
                                            const table = document.getElementById("packing-list-table");
                                            
                                            if (!searchInput || !orderFilterInput || !table) {
                                                setTimeout(initPackingListFilters, 100);
                                                return;
                                            }
                                            
                                            function filterTable() {
                                                const searchTerm = (searchInput.value || "").toLowerCase().trim();
                                                const orderFilter = (orderFilterInput.value || "").toUpperCase().trim();
                                                const rows = table.querySelectorAll("tbody tr");
                                                let visibleCount = 0;
                                                
                                                rows.forEach(function(row) {
                                                    if (row.id === "packing-list-no-results") {
                                                        return;
                                                    }
                                                    
                                                    // Get row text for general search
                                                    const rowText = row.textContent.toLowerCase();
                                                    
                                                    // Get order allocations from the Order Allocations column (6th column, index 5)
                                                    // Column order: CTN#(0), Style(1), Color(2), Packing Way(3), Original Qty(4), Order Allocations(5)
                                                    const cells = row.querySelectorAll("td");
                                                    let hasMatchingOrder = true;
                                                    
                                                    if (orderFilter) {
                                                        hasMatchingOrder = false;
                                                        if (cells.length > 5) {
                                                            const orderAllocationsCell = cells[5];
                                                            // Get text content including from nested elements (badges, spans, etc.)
                                                            const orderText = orderAllocationsCell.textContent.toUpperCase();
                                                            // Also check innerHTML for order numbers in HTML attributes or nested elements
                                                            const orderHtml = orderAllocationsCell.innerHTML.toUpperCase();
                                                            
                                                            // Check if order number appears in text or HTML
                                                            if (orderText.includes(orderFilter) || orderHtml.includes(orderFilter)) {
                                                                hasMatchingOrder = true;
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Check if row matches both filters
                                                    const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                                                    const matchesOrder = hasMatchingOrder;
                                                    
                                                    if (matchesSearch && matchesOrder) {
                                                        row.style.display = "";
                                                        visibleCount++;
                                                    } else {
                                                        row.style.display = "none";
                                                    }
                                                });
                                                
                                                // Show/hide "no results" message
                                                let noResults = document.getElementById("packing-list-no-results");
                                                if (visibleCount === 0 && (searchTerm || orderFilter)) {
                                                    if (!noResults) {
                                                        noResults = document.createElement("tr");
                                                        noResults.id = "packing-list-no-results";
                                                        noResults.innerHTML = \'<td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No items found matching your filters.</td>\';
                                                        table.querySelector("tbody").appendChild(noResults);
                                                    }
                                                    noResults.style.display = "";
                                                } else if (noResults) {
                                                    noResults.style.display = "none";
                                                }
                                            }
                                            
                                            searchInput.addEventListener("input", filterTable);
                                            searchInput.addEventListener("keyup", filterTable);
                                            orderFilterInput.addEventListener("input", filterTable);
                                            orderFilterInput.addEventListener("keyup", filterTable);
                                            
                                            // Also filter on page load if there\'s a value
                                            if (searchInput.value || orderFilterInput.value) {
                                                filterTable();
                                            }
                                        }
                                        
                                        if (document.readyState === "loading") {
                                            document.addEventListener("DOMContentLoaded", initPackingListFilters);
                                        } else {
                                            initPackingListFilters();
                                        }
                                    })();
                                </script>
                                </div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
                
                // Pick List Guides section removed
                /*
                Infolists\Components\Section::make('Pick List Guides')
                    ->description('Upload pick list files above to see which cartons contain the items you need. Click on a pick list to view it in a new page.')
                    ->columnSpanFull()
                    ->visible(function () {
                        return !empty($this->pickLists) && is_array($this->pickLists) && count($this->pickLists) > 0;
                    })
                    ->schema(function () {
                        $entries = [];
                        
                        if (empty($this->pickLists) || !is_array($this->pickLists)) {
                            return [];
                        }
                        
                        foreach ($this->pickLists as $pickListIndex => $pickList) {
                            $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
                            $fileName = $pickList['filename'] ?? 'Unknown';
                            $uploadedAt = $pickList['uploaded_at'] ?? '';
                            $pickListId = $pickList['id'] ?? uniqid('pl_', true);
                            
                            // Format uploaded date
                            $uploadedAtFormatted = '';
                            if ($uploadedAt) {
                                try {
                                    $uploadedAtFormatted = \Carbon\Carbon::parse($uploadedAt)->format('M d, Y g:i A');
                                } catch (\Exception $e) {
                                    $uploadedAtFormatted = $uploadedAt;
                                }
                            }
                            
                            // Count items
                            $itemCount = count($pickList['items'] ?? []);
                            
                            // Count picked items
                            $pickedItems = $pickList['picked_items'] ?? [];
                            $totalPicked = 0;
                            foreach ($pickedItems as $picked) {
                                $totalPicked += $picked['quantity_picked'] ?? 0;
                            }
                            
                            // Calculate total needed
                            $totalNeeded = 0;
                            foreach ($pickList['items'] ?? [] as $item) {
                                $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
                            }
                            
                            $pickListUrl = IncomingShipmentResource::getUrl('view-pick-list', [
                                'shipmentId' => $this->record->id,
                                'pickListIndex' => $pickListIndex,
                            ]);
                            
                            $entries[] = Infolists\Components\TextEntry::make('pick_list_link_' . $pickListIndex)
                                ->label('')
                                ->html()
                                ->formatStateUsing(function () use ($pickListName, $fileName, $uploadedAtFormatted, $itemCount, $totalNeeded, $totalPicked, $pickListIndex, $pickListId, $pickListUrl) {
                                    $remaining = max(0, $totalNeeded - $totalPicked);
                                    $progressPercent = $totalNeeded > 0 ? round(($totalPicked / $totalNeeded) * 100) : 0;
                                    
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <a href="' . $pickListUrl . '" class="text-lg font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                                            ' . htmlspecialchars($pickListName) . '
                                                            <svg class="inline-block h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    </div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                                        <div>File: ' . htmlspecialchars($fileName) . '</div>
                                                        ' . ($uploadedAtFormatted ? '<div>Uploaded: ' . htmlspecialchars($uploadedAtFormatted) . '</div>' : '') . '
                                                    </div>
                                                    <div class="flex items-center gap-4 text-sm">
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong>' . number_format($itemCount) . '</strong> items
                                                        </span>
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong>' . number_format($totalNeeded) . '</strong> pcs needed
                                                        </span>
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong class="text-green-600 dark:text-green-400">' . number_format($totalPicked) . '</strong> pcs picked
                                                        </span>
                                                        <span class="text-gray-600 dark:text-gray-400">
                                                            <strong class="text-orange-600 dark:text-orange-400">' . number_format($remaining) . '</strong> pcs remaining
                                                        </span>
                                                    </div>
                                                    ' . ($totalNeeded > 0 ? '
                                                    <div class="mt-3">
                                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                            <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: ' . $progressPercent . '%"></div>
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">' . $progressPercent . '% complete</div>
                                                    </div>
                                                    ' : '') . '
                                                </div>
                                                <div class="ml-4 flex items-center gap-2">
                                                    <a href="' . $pickListUrl . '" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                                                        View Pick List
                                                        <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    ');
                                })
                                ->columnSpanFull();
                        }
                        
                        return $entries;
                    }),
                */
                
                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->default('—')
                            ->placeholder('No notes'),
                    ])
                    ->visible(fn ($record) => !empty($record->notes)),
            ]);
    }
    
    protected function parsePickListFile(string $filePath): array
    {
        $items = [];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $items = $this->parsePickListCsv($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            $items = $this->parsePickListCsv($filePath); // Use CSV parser for Excel
        } elseif ($extension === 'pdf') {
            $items = $this->parsePickListPdf($filePath);
        }
        
        return $items;
    }
    
    protected function parsePickListCsv(string $filePath): array
    {
        $items = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return $items;
        }
        
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return $items;
        }
        
        // Map columns - look for style, color, quantity, description, order number
        $headerMap = [];
        foreach ($header as $index => $col) {
            $colLower = strtolower(trim($col));
            if (preg_match('/style|product|item/i', $colLower)) {
                $headerMap['style'] = $index;
            } elseif (preg_match('/color|colour/i', $colLower)) {
                $headerMap['color'] = $index;
            } elseif (preg_match('/quantity|qty|amount|#|pieces|pcs|required/i', $colLower)) {
                $headerMap['quantity'] = $index;
            } elseif (preg_match('/description|item.*description|product.*name/i', $colLower)) {
                $headerMap['description'] = $index;
            } elseif (preg_match('/packing.*way|way.*packing/i', $colLower)) {
                $headerMap['packing_way'] = $index;
            } elseif (preg_match('/order|order.*number|order.*#|order.*num|order.*id/i', $colLower)) {
                $headerMap['order_number'] = $index;
            }
        }
        
        // Fallback: assume first columns are style, color, quantity
        if (empty($headerMap) && count($header) >= 3) {
            $headerMap = [
                'style' => 0,
                'color' => 1,
                'quantity' => 2,
            ];
        }
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            
            $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) 
                ? trim($row[$headerMap['description']]) : '';
            $style = isset($headerMap['style']) && isset($row[$headerMap['style']]) 
                ? trim($row[$headerMap['style']]) : '';
            $color = isset($headerMap['color']) && isset($row[$headerMap['color']]) 
                ? trim($row[$headerMap['color']]) : '';
            $quantityRaw = isset($headerMap['quantity']) && isset($row[$headerMap['quantity']]) 
                ? trim($row[$headerMap['quantity']]) : '';
            $packingWay = isset($headerMap['packing_way']) && isset($row[$headerMap['packing_way']]) 
                ? trim($row[$headerMap['packing_way']]) : '';
            $orderNumber = isset($headerMap['order_number']) && isset($row[$headerMap['order_number']]) 
                ? trim($row[$headerMap['order_number']]) : '';
            
            // If we have a description, try to parse it
            if (!empty($description) && (empty($style) || empty($color))) {
                $parsed = \App\Models\Order::parseOrderDescription($description);
                if (empty($style)) {
                    $style = $parsed['style'];
                }
                if (empty($color)) {
                    $color = $parsed['color'];
                }
                if (empty($packingWay)) {
                    $packingWay = $parsed['packing_way'];
                }
            }
            
            // Try to extract order number from description if not in separate column
            if (empty($orderNumber) && !empty($description)) {
                // Look for patterns like "BDR1399", "Order BDR1399", "BDR-1399", etc.
                if (preg_match('/\b([A-Z]{2,}\d{3,})\b/i', $description, $matches)) {
                    $orderNumber = strtoupper($matches[1]);
                } elseif (preg_match('/order\s*[#:]?\s*([A-Z0-9-]+)/i', $description, $matches)) {
                    $orderNumber = strtoupper(trim($matches[1]));
                }
            }
            
            // Also try to extract from style column if it contains order info
            if (empty($orderNumber) && !empty($style)) {
                if (preg_match('/\b([A-Z]{2,}\d{3,})\b/i', $style, $matches)) {
                    $orderNumber = strtoupper($matches[1]);
                }
            }
            
            if (empty($style) && empty($color)) {
                continue;
            }
            
            // Extract quantity
            $quantity = 0;
            if (!empty($quantityRaw)) {
                preg_match('/\d+/', $quantityRaw, $matches);
                $quantity = !empty($matches) ? (int)$matches[0] : 0;
            }
            
            if ($quantity <= 0) {
                continue;
            }
            
            $items[] = [
                'style' => trim($style),
                'color' => trim($color),
                'packing_way' => !empty($packingWay) ? trim($packingWay) : 'hook',
                'quantity' => $quantity,
                'order_number' => !empty($orderNumber) ? trim($orderNumber) : null,
            ];
        }
        
        fclose($handle);
        return $items;
    }
    
    protected function parsePickListPdf(string $filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                \Log::error('PDF file does not exist', ['path' => $filePath]);
                return [];
            }
            
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            \Log::info('PDF parsing started', [
                'file_path' => $filePath,
                'text_length' => strlen($text),
                'text_preview' => substr($text, 0, 2000),
            ]);
            
            $lines = explode("\n", $text);
            $items = [];
            
            // Look for table structure - Description and # Required columns
            $headerFound = false;
            $descriptionIndex = -1;
            $quantityIndex = -1;
            
            // Find header row
            foreach ($lines as $index => $line) {
                $lineLower = strtolower(trim($line));
                if (preg_match('/description/i', $lineLower) && preg_match('/required|quantity|qty/i', $lineLower)) {
                    $headerFound = true;
                    // Try to split by pipes or multiple spaces
                    $parts = preg_split('/\s*\|\s*/', $line);
                    if (count($parts) < 3) {
                        $parts = preg_split('/\s{2,}/', $line);
                    }
                    
                    foreach ($parts as $colIndex => $col) {
                        $colLower = strtolower(trim($col));
                        if (preg_match('/description/i', $colLower)) {
                            $descriptionIndex = $colIndex;
                        } elseif (preg_match('/required|quantity|qty|#/i', $colLower)) {
                            $quantityIndex = $colIndex;
                        }
                    }
                    break;
                }
            }
            
            // If header found, parse rows
            if ($headerFound && $descriptionIndex >= 0 && $quantityIndex >= 0) {
                foreach ($lines as $lineIndex => $line) {
                    $line = trim($line);
                    if (empty($line) || preg_match('/^item\s*#|^description|^warehouse|^required|^total|^orders:/i', $line)) {
                        continue;
                    }
                    
                    // Try splitting by pipes first, then multiple spaces
                    $parts = preg_split('/\s*\|\s*/', $line);
                    if (count($parts) < 3) {
                        $parts = preg_split('/\s{2,}/', $line);
                    }
                    
                    // If we don't have enough parts, try looking at next line for quantity
                    $description = '';
                    $quantityRaw = '';
                    
                    if (count($parts) > max($descriptionIndex, $quantityIndex)) {
                        $description = isset($parts[$descriptionIndex]) ? trim($parts[$descriptionIndex]) : '';
                        $quantityRaw = isset($parts[$quantityIndex]) ? trim($parts[$quantityIndex]) : '';
                    } elseif (preg_match('/bodyrok/i', $line)) {
                        // Description might be on this line, quantity on next
                        $description = $line;
                        if (isset($lines[$lineIndex + 1])) {
                            $nextLine = trim($lines[$lineIndex + 1]);
                            if (is_numeric($nextLine)) {
                                $quantityRaw = $nextLine;
                            }
                        }
                    }
                    
                    // If description contains BODYROK, parse it
                    if (preg_match('/bodyrok/i', $description)) {
                        // Clean description - remove any trailing quantity that might have been merged
                        // Pattern: description might end with "Hook 50" or "Sleeve Wrap 100"
                        $cleanDescription = preg_replace('/\s+(\d+)\s*(?:pcs|pieces|qty|quantity)?\s*$/i', '', $description);
                        
                        $parsed = \App\Models\Order::parseOrderDescription($cleanDescription);
                        if (!empty($parsed['style'])) {
                            // Extract quantity - prioritize the separate quantity column
                            $quantity = 0;
                            if (!empty($quantityRaw)) {
                                preg_match('/\d+/', $quantityRaw, $qtyMatches);
                                $quantity = !empty($qtyMatches) ? (int)$qtyMatches[0] : 0;
                            }
                            
                            // If no quantity from column, try to extract from description end
                            if ($quantity === 0) {
                                preg_match('/(\d+)\s*(?:pcs|pieces|qty|quantity)?\s*$/i', $description, $qtyMatches);
                                $quantity = !empty($qtyMatches) ? (int)$qtyMatches[1] : 0;
                            }
                            
                            if ($quantity > 0) {
                                // Try to extract order number from description
                                $orderNumber = null;
                                if (preg_match('/\b([A-Z]{2,}\d{3,})\b/i', $description, $orderMatches)) {
                                    $orderNumber = strtoupper($orderMatches[1]);
                                } elseif (preg_match('/order\s*[#:]?\s*([A-Z0-9-]+)/i', $description, $orderMatches)) {
                                    $orderNumber = strtoupper(trim($orderMatches[1]));
                                }
                                
                                $items[] = [
                                    'style' => $parsed['style'],
                                    'color' => $parsed['color'],
                                    'packing_way' => $parsed['packing_way'],
                                    'quantity' => $quantity,
                                    'order_number' => $orderNumber,
                                ];
                            }
                        }
                    }
                }
            } else {
                // Fallback: parse line by line looking for BODYROK descriptions
                // Handle table format where description and quantity might be on same or adjacent lines
                $currentDescription = '';
                $currentQuantity = 0;
                $lineIndex = 0;
                
                foreach ($lines as $lineIndex => $line) {
                    $line = trim($line);
                    if (empty($line) || preg_match('/^item\s*#|^description|^warehouse|^required|^total|^orders:/i', $line)) {
                        continue;
                    }
                    
                    // Check if line contains BODYROK description or looks like a product description
                    if (preg_match('/bodyrok|crew|tall|ankle|quarter|no-show|sock/i', $line)) {
                        // Try to extract quantity from same line first
                        preg_match('/(\d+)\s*(?:pcs|pieces|qty|quantity)?/i', $line, $qtyMatches);
                        $quantityFromLine = !empty($qtyMatches) ? (int)$qtyMatches[1] : 0;
                        
                        // Check if quantity is at the end of the line (common in table extraction)
                        // Look for standalone numbers that might be quantities
                        $lineParts = preg_split('/\s{2,}|\t/', $line);
                        $lastPart = end($lineParts);
                        if (is_numeric(trim($lastPart)) && (int)trim($lastPart) > 0 && (int)trim($lastPart) < 10000) {
                            $quantityFromLine = (int)trim($lastPart);
                        }
                        
                        if ($quantityFromLine > 0) {
                            // We have both description and quantity on same line
                            $parsed = \App\Models\Order::parseOrderDescription($line);
                            if (!empty($parsed['style'])) {
                                // Try to extract order number from line
                                $orderNumber = null;
                                if (preg_match('/\b([A-Z]{2,}\d{3,})\b/i', $line, $orderMatches)) {
                                    $orderNumber = strtoupper($orderMatches[1]);
                                } elseif (preg_match('/order\s*[#:]?\s*([A-Z0-9-]+)/i', $line, $orderMatches)) {
                                    $orderNumber = strtoupper(trim($orderMatches[1]));
                                }
                                
                                $items[] = [
                                    'style' => $parsed['style'],
                                    'color' => $parsed['color'],
                                    'packing_way' => $parsed['packing_way'],
                                    'quantity' => $quantityFromLine,
                                    'order_number' => $orderNumber,
                                ];
                            } else {
                                \Log::warning('Could not parse line with quantity', [
                                    'line' => $line,
                                    'quantity' => $quantityFromLine,
                                ]);
                            }
                        } else {
                            // Description on this line, quantity might be on next line
                            $currentDescription = $line;
                        }
                    } elseif (is_numeric(trim($line)) && !empty($currentDescription)) {
                        // This might be the quantity on a separate line
                        $quantity = (int)trim($line);
                        if ($quantity > 0 && $quantity < 10000) {
                            $currentQuantity = $quantity;
                            
                            // Process the item
                            $parsed = \App\Models\Order::parseOrderDescription($currentDescription);
                            if (!empty($parsed['style'])) {
                                // Try to extract order number from description
                                $orderNumber = null;
                                if (preg_match('/\b([A-Z]{2,}\d{3,})\b/i', $currentDescription, $orderMatches)) {
                                    $orderNumber = strtoupper($orderMatches[1]);
                                } elseif (preg_match('/order\s*[#:]?\s*([A-Z0-9-]+)/i', $currentDescription, $orderMatches)) {
                                    $orderNumber = strtoupper(trim($orderMatches[1]));
                                }
                                
                                $items[] = [
                                    'style' => $parsed['style'],
                                    'color' => $parsed['color'],
                                    'packing_way' => $parsed['packing_way'],
                                    'quantity' => $currentQuantity,
                                    'order_number' => $orderNumber,
                                ];
                            }
                            $currentDescription = '';
                            $currentQuantity = 0;
                        }
                    }
                }
                
                // Process any remaining description with quantity
                if (!empty($currentDescription) && $currentQuantity > 0) {
                    $parsed = \App\Models\Order::parseOrderDescription($currentDescription);
                    if (!empty($parsed['style'])) {
                        $items[] = [
                            'style' => $parsed['style'],
                            'color' => $parsed['color'],
                            'packing_way' => $parsed['packing_way'],
                            'quantity' => $currentQuantity,
                        ];
                    }
                }
            }
            
            \Log::info('Parsed ' . count($items) . ' items from pick list PDF', [
                'file_path' => $filePath,
                'items' => $items,
            ]);
            
            if (empty($items)) {
                \Log::warning('No items parsed from PDF', [
                    'file_path' => $filePath,
                    'text_length' => strlen($text ?? ''),
                    'text_sample' => substr($text ?? '', 0, 1000),
                    'header_found' => $headerFound ?? false,
                    'description_index' => $descriptionIndex ?? -1,
                    'quantity_index' => $quantityIndex ?? -1,
                ]);
            }
            
            return $items;
        } catch (\Exception $e) {
            \Log::error('Error parsing pick list PDF', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}
