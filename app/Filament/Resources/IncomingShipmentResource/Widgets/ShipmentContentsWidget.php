<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

class ShipmentContentsWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.shipment-contents-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?IncomingShipment $record = null;

    public array $items = [];
    public array $selectedRows = [];
    public bool $selectAll = false;
    public array $bulkUpdateData = [
        'carton_number' => '',
        'order_number' => '',
        'quantity' => '',
    ];
    public bool $isEditable = true; // Default to true for edit pages
    public bool $canReceiveQty = true; // Always allow receiving quantities
    public bool $isEditPage = false; // Store whether we're on edit page

    public function mount(): void
    {
        // Check if we're on edit page by checking path and referrer
        $path = request()->path();
        $referrer = request()->header('referer', '');
        
        // Check path first, then referrer (for Livewire updates)
        $isEditPage = str_contains($path, '/edit') || str_contains($referrer, '/edit');
        
        // Store the page type
        $this->isEditPage = $isEditPage;
        
        // Set editable based on page type
        if ($isEditPage) {
            $this->isEditable = true; // Editable on edit page
            $this->canReceiveQty = true;
        } else {
            $this->isEditable = false; // Not editable on view page
            $this->canReceiveQty = true; // Always allow receiving quantities
        }
        
        $this->loadItems();
    }

    public function boot(): void
    {
        // Only set on initial boot, preserve the state set in mount()
        // This prevents Livewire updates from resetting the state
        if (!$this->isEditPage) {
            $path = request()->path();
            $referrer = request()->header('referer', '');
            
            // Check path first, then referrer (for Livewire updates)
            $isEditPage = str_contains($path, '/edit') || str_contains($referrer, '/edit');
            
            // Store the page type
            $this->isEditPage = $isEditPage;
            
            // Set editable based on page type
            if ($isEditPage) {
                $this->isEditable = true; // Editable on edit page
                $this->canReceiveQty = true;
            } else {
                $this->isEditable = false; // Not editable on view page
                $this->canReceiveQty = true; // Always allow receiving quantities
            }
        }
    }

    public function getIsEditableProperty(): bool
    {
        // Always check path first - most reliable
        $path = request()->path();
        
        // If path contains 'edit', it's definitely editable
        if (str_contains($path, '/edit')) {
            return true;
        }
        
        // Return the property value - it's set correctly in mount() and boot()
        return $this->isEditable;
    }

    public function updatingRecord(): void
    {
        // Reload items when record changes and re-check edit mode
        $routeName = request()->route()?->getName() ?? '';
        $isViewPage = str_contains($routeName, '.view') || str_contains($routeName, 'view.');
        
        if ($isViewPage) {
            $this->isEditable = false; // All fields read-only except received_qty
            $this->canReceiveQty = true; // Always allow receiving quantities
        } else {
            // Default to editable for edit pages
            $this->isEditable = true;
            $this->canReceiveQty = true;
        }
        
        $this->loadItems();
    }

    public function loadItems(): void
    {
        if (!$this->record) {
            $this->items = [];
            return;
        }

        // Get items from record
        $savedItems = $this->record->items ?? [];
        
        if (!is_array($savedItems)) {
            $this->items = [];
            return;
        }

        // Convert saved items to editable format
        $this->items = [];
        foreach ($savedItems as $item) {
            $style = $item['style'] ?? '';
            $color = $item['color'] ?? '';
            $productName = '';
            if (!empty($style) && !empty($color)) {
                $productName = $style . ' - ' . $color;
            } elseif (!empty($style)) {
                $productName = $style;
            } elseif (!empty($color)) {
                $productName = $color;
            }

            $this->items[] = [
                'carton_number' => $item['carton_number'] ?? '',
                'order_number' => $item['order_number'] ?? '',
                'eid' => $item['eid'] ?? '',
                'product_name' => $productName,
                'quantity' => $item['quantity'] ?? 0,
                'received_qty' => $item['received_qty'] ?? 0,
                'is_saved' => true, // Mark as saved since it came from the database
            ];
        }

        // If on edit page, sync initial items to form
        if ($this->isEditable) {
            $this->syncItemsToForm();
        }
    }

    #[On('record-updated')]
    public function refreshItems(): void
    {
        $this->loadItems();
    }

    public function updatedItems(): void
    {
        if ($this->isEditable || $this->canReceiveQty) {
            if ($this->isEditable) {
                $this->syncItemsToForm();
            } else {
                // On view page, save directly to record
                $this->saveReceivedQuantities();
            }
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedRows = array_keys($this->items);
        } else {
            $this->selectedRows = [];
        }
    }

    public function updatedSelectedRows(): void
    {
        $this->selectAll = count($this->selectedRows) === count($this->items) && count($this->items) > 0;
    }

    public function bulkUpdateSelected(): void
    {
        foreach ($this->selectedRows as $index) {
            if (isset($this->items[$index])) {
                if (!empty($this->bulkUpdateData['carton_number'])) {
                    $this->items[$index]['carton_number'] = $this->bulkUpdateData['carton_number'];
                }
                if (!empty($this->bulkUpdateData['order_number'])) {
                    $this->items[$index]['order_number'] = $this->bulkUpdateData['order_number'];
                }
                if (!empty($this->bulkUpdateData['quantity'])) {
                    $this->items[$index]['quantity'] = $this->bulkUpdateData['quantity'];
                }
            }
        }
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->bulkUpdateData = [
            'carton_number' => '',
            'order_number' => '',
            'quantity' => '',
        ];
        $this->syncItemsToForm();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'carton_number' => '',
            'order_number' => '',
            'eid' => '',
            'product_name' => '',
            'quantity' => '',
            'received_qty' => 0,
            'is_saved' => false, // New item, not yet saved
        ];
        $this->syncItemsToForm();
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->syncItemsToForm();
    }

    public function receiveFullQty(int $index): void
    {
        if (isset($this->items[$index])) {
            $quantity = (int)($this->items[$index]['quantity'] ?? 0);
            $this->items[$index]['received_qty'] = $quantity;
            
            if ($this->isEditable) {
                $this->syncItemsToForm();
            } else {
                // On view page, save directly to record
                $this->saveReceivedQuantities();
            }
        }
    }

    protected function saveReceivedQuantities(): void
    {
        if (!$this->record) {
            return;
        }

        // Track receive history
        $receiveHistory = $this->record->receive_history ?? [];
        $oldItems = $this->record->items ?? [];
        $hasReceivedItems = false;

        // Convert items back to form format
        $formItems = [];
        foreach ($this->items as $index => $item) {
            $productName = $item['product_name'] ?? '';
            $style = '';
            $color = '';
            $packingWay = 'Hook';
            
            if (!empty($productName)) {
                $parts = explode(' - ', $productName);
                if (count($parts) >= 2) {
                    $style = trim($parts[0]);
                    $color = trim($parts[1]);
                    if (count($parts) >= 3) {
                        $lastPart = trim($parts[count($parts) - 1]);
                        if (stripos($lastPart, 'sleeve') !== false || stripos($lastPart, 'wrap') !== false) {
                            $packingWay = 'Sleeve Wrap';
                            $color = trim($parts[count($parts) - 2]);
                        }
                    }
                } else {
                    $style = trim($productName);
                    $color = '';
                }
            }
            
            $oldItem = $oldItems[$index] ?? null;
            $oldReceivedQty = (int)($oldItem['received_qty'] ?? 0);
            $newReceivedQty = (int)($item['received_qty'] ?? 0);
            
            // Track receive history if received_qty changed
            if ($newReceivedQty > 0 && $newReceivedQty != $oldReceivedQty) {
                $hasReceivedItems = true;
                
                $receiveHistory[] = [
                    'item_index' => $index,
                    'carton_number' => $item['carton_number'] ?? '',
                    'order_number' => $item['order_number'] ?? '',
                    'eid' => $item['eid'] ?? '',
                    'product_name' => $productName,
                    'quantity' => (int)($item['quantity'] ?? 0),
                    'received_qty' => $newReceivedQty,
                    'previous_received_qty' => $oldReceivedQty,
                    'received_at' => now()->toDateTimeString(),
                    'received_by' => auth()->id(),
                ];
            }
            
            $formItems[] = [
                'carton_number' => $item['carton_number'] ?? '',
                'order_number' => $item['order_number'] ?? '',
                'style' => $style,
                'color' => $color,
                'eid' => $item['eid'] ?? '',
                'packing_way' => $packingWay,
                'quantity' => !empty($item['quantity']) ? (int)$item['quantity'] : 0,
                'received_qty' => !empty($item['received_qty']) ? (int)$item['received_qty'] : 0,
                'is_saved' => $item['is_saved'] ?? false, // Preserve saved status
            ];
        }

        // Update record directly in database without mutating reactive prop
        $updateData = ['items' => $formItems];
        
        // Track first receive time
        if ($hasReceivedItems && empty($this->record->first_received_at)) {
            $updateData['first_received_at'] = now();
        }
        
        // Update receive history
        if ($hasReceivedItems) {
            $updateData['receive_history'] = $receiveHistory;
        }
        
        // Calculate and update status based on received quantities
        $tempRecord = clone $this->record;
        $tempRecord->items = $formItems;
        $calculatedStatus = $tempRecord->calculateStatusFromReceivedQuantities();
        $updateData['status'] = $calculatedStatus;
        
        // Update the record directly using the model (not the reactive prop)
        IncomingShipment::where('id', $this->record->id)->update($updateData);
        
        // Reload items from database to reflect changes (without mutating reactive prop)
        $freshRecord = IncomingShipment::find($this->record->id);
        if ($freshRecord) {
            // Update items array from fresh database record without mutating reactive prop
            $savedItems = $freshRecord->items ?? [];
            if (is_array($savedItems)) {
                $this->items = [];
                foreach ($savedItems as $item) {
                    $style = $item['style'] ?? '';
                    $color = $item['color'] ?? '';
                    $productName = '';
                    if (!empty($style) && !empty($color)) {
                        $productName = $style . ' - ' . $color;
                    } elseif (!empty($style)) {
                        $productName = $style;
                    } elseif (!empty($color)) {
                        $productName = $color;
                    }

                    $this->items[] = [
                        'carton_number' => $item['carton_number'] ?? '',
                        'order_number' => $item['order_number'] ?? '',
                        'eid' => $item['eid'] ?? '',
                        'product_name' => $productName,
                        'quantity' => $item['quantity'] ?? 0,
                        'received_qty' => $item['received_qty'] ?? 0,
                        'is_saved' => true,
                    ];
                }
            }
        }
    }

    public function updated($propertyName): void
    {
        // Allow syncing if editable or if it's a received_qty change
        $isReceivedQtyChange = str_contains($propertyName, '.received_qty');
        
        if (!$this->isEditable && !$isReceivedQtyChange) {
            return;
        }
        
        // Sync when any item property changes
        if (str_starts_with($propertyName, 'items.')) {
            // Validate received_qty is not negative (but allow it to exceed quantity)
            if ($isReceivedQtyChange) {
                $parts = explode('.', $propertyName);
                if (count($parts) >= 2 && is_numeric($parts[1])) {
                    $index = (int)$parts[1];
                    if (isset($this->items[$index])) {
                        $receivedQty = (int)($this->items[$index]['received_qty'] ?? 0);
                        // Only prevent negative values, allow exceeding quantity
                        if ($receivedQty < 0) {
                            $this->items[$index]['received_qty'] = 0;
                        }
                    }
                }
            }
            
            // Sync to form if editable, or save directly if on view page
            if ($this->isEditable) {
                $this->syncItemsToForm();
            } elseif ($isReceivedQtyChange) {
                // On view page, save received quantities directly to record
                $this->saveReceivedQuantities();
            }
        }
    }

    public function syncItemsToForm(): void
    {
        // Convert items back to form format
        $formItems = [];
        foreach ($this->items as $item) {
            // Skip incomplete rows - all fields except received_qty must be filled
            $cartonNumber = trim($item['carton_number'] ?? '');
            $orderNumber = trim($item['order_number'] ?? '');
            $eid = trim($item['eid'] ?? '');
            $productName = trim($item['product_name'] ?? '');
            $quantity = trim($item['quantity'] ?? '');
            
            // Skip rows where any required field is empty
            if (empty($cartonNumber) || empty($orderNumber) || empty($eid) || empty($productName) || empty($quantity)) {
                continue;
            }
            
            $style = '';
            $color = '';
            $packingWay = 'Hook';
            
            if (!empty($productName)) {
                $parts = explode(' - ', $productName);
                if (count($parts) >= 2) {
                    $style = trim($parts[0]);
                    $color = trim($parts[1]);
                    if (count($parts) >= 3) {
                        $lastPart = trim($parts[count($parts) - 1]);
                        if (stripos($lastPart, 'sleeve') !== false || stripos($lastPart, 'wrap') !== false) {
                            $packingWay = 'Sleeve Wrap';
                            $color = trim($parts[count($parts) - 2]);
                        }
                    }
                } else {
                    $style = trim($productName);
                    $color = '';
                }
            }
            
            $formItems[] = [
                'carton_number' => $cartonNumber,
                'order_number' => $orderNumber,
                'style' => $style,
                'color' => $color,
                'eid' => $eid,
                'packing_way' => $packingWay,
                'quantity' => !empty($quantity) ? (int)$quantity : 0,
                'received_qty' => !empty($item['received_qty']) ? (int)$item['received_qty'] : 0,
                'is_saved' => $item['is_saved'] ?? false, // Preserve saved status
            ];
        }

        // Dispatch event to update parent form
        $this->dispatch('update-shipment-items', items: $formItems);
    }

    public function saveChanges(): void
    {
        // Sync items to form first - this dispatches an event to update the parent form
        $this->syncItemsToForm();
        
        // Dispatch event to trigger form submission after sync completes
        $this->dispatch('save-shipment-form');
    }
}

