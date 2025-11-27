<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;

class PickListTableWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.pick-list-table-widget';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?IncomingShipment $shipment = null;
    public array $selectedItems = [];
    
    // Poll the widget every 5 seconds to check for updates
    protected static ?string $pollingInterval = '5s';
    
    public static function canView(): bool
    {
        $route = request()->route();
        if (!$route) {
            return false;
        }
        
        $routeName = $route->getName();
        return str_contains($routeName, 'incoming-shipments.view');
    }
    
    public function mount(): void
    {
        $this->loadShipment();
    }
    
    protected function loadShipment(): void
    {
        $recordId = request()->route('record');
        if ($recordId) {
            // Always reload from database to get latest pick lists
            $this->shipment = IncomingShipment::find($recordId);
            if ($this->shipment) {
                // Refresh the model to ensure we have latest data
                $this->shipment->refresh();
            }
        }
    }
    
    protected function getListeners(): array
    {
        return [
            'pick-list-updated' => 'refreshWidget',
            '$refresh' => 'refreshWidget',
            'refresh-widgets' => 'refreshWidget',
        ];
    }
    
    public function refreshWidget(): void
    {
        // Clear any cached data and force reload
        $this->shipment = null; // Force reload
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
    
    public function getTableData(): array
    {
        // Reload shipment to get latest data
        $this->loadShipment();
        
        if (!$this->shipment) {
            return [];
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        if (!is_array($pickLists) || empty($pickLists)) {
            return [];
        }
        
        $allItems = [];
        foreach ($pickLists as $pickListIndex => $pickList) {
            $pickListItems = $pickList['items'] ?? [];
            $pickedItems = $pickList['picked_items'] ?? [];
            $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
            
            foreach ($pickListItems as $itemIndex => $item) {
                if (isset($item['description'])) {
                    $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                    $style = $parsed['style'] ?? '';
                    $color = $parsed['color'] ?? '';
                    $packingWay = $parsed['packing_way'] ?? 'hook';
                    $quantityNeeded = $item['quantity_required'] ?? $item['quantity'] ?? 0;
                } else {
                    $style = $item['style'] ?? '';
                    $color = $item['color'] ?? '';
                    $packingWay = $item['packing_way'] ?? 'hook';
                    $quantityNeeded = $item['quantity'] ?? 0;
                }
                
                $quantityPicked = 0;
                foreach ($pickedItems as $picked) {
                    if (($picked['item_index'] ?? null) === $itemIndex) {
                        $quantityPicked += $picked['quantity_picked'] ?? 0;
                    }
                }
                
                $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
                $availableCartons = $this->shipment->getAvailableCartonsForItem($style, $color, $packingWay, $quantityRemaining, [$pickList]);
                
                $cartonGuidance = '';
                if ($quantityRemaining === 0) {
                    $cartonGuidance = '<span class="text-green-600 dark:text-green-400 font-semibold">✓ Fully Picked</span>';
                } elseif (!empty($availableCartons['cartons'])) {
                    $firstCarton = $availableCartons['cartons'][0];
                    $cartonGuidance = '<span class="text-green-600 dark:text-green-400 font-semibold">✓ Pick from: <strong>CTN#' . $firstCarton['carton_number'] . '</strong> (' . number_format($firstCarton['available_quantity']) . ' pcs available)';
                    
                    if ($quantityRemaining > $firstCarton['available_quantity'] && count($availableCartons['cartons']) > 1) {
                        $additional = collect(array_slice($availableCartons['cartons'], 1))
                            ->map(fn($c) => 'CTN#' . $c['carton_number'] . ' (' . number_format($c['available_quantity']) . ' pcs)')
                            ->implode(', ');
                        $cartonGuidance .= ' • Also check: ' . $additional;
                    }
                    $cartonGuidance .= '</span>';
                } else {
                    $cartonGuidance = '<span class="text-red-600 dark:text-red-400 font-semibold">✗ No matching cartons found</span>';
                }
                
                $matchingShipmentIndex = null;
                if (!empty($this->shipment->items) && is_array($this->shipment->items)) {
                    $normalizedStyle = strtolower(trim($style));
                    $normalizedColor = trim(strtolower(trim($color)), ' -');
                    $normalizedPackingWay = strtolower(trim($packingWay));
                    
                    if (strpos($normalizedPackingWay, 'hook') !== false) {
                        $normalizedPackingWay = 'hook';
                    } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                        $normalizedPackingWay = 'sleeve wrap';
                    }
                    
                    foreach ($this->shipment->items as $shipIndex => $shipItem) {
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
                
                $allItems[] = [
                    'id' => $pickListIndex . '_' . $itemIndex,
                    'pick_list_index' => $pickListIndex,
                    'pick_list_name' => $pickListName,
                    'item_index' => $itemIndex,
                    'shipment_item_index' => $matchingShipmentIndex,
                    'style' => $style,
                    'color' => $color,
                    'packing_way' => $packingWay,
                    'quantity_needed' => $quantityNeeded,
                    'quantity_picked' => $quantityPicked,
                    'quantity_remaining' => $quantityRemaining,
                    'carton_guidance' => $cartonGuidance,
                    'can_fulfill' => !empty($availableCartons['cartons']),
                ];
            }
        }
        
        return $allItems;
    }
    
    public function markItemAsPicked(string $itemId): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $parts = explode('_', $itemId);
        if (count($parts) !== 2) {
            return;
        }
        
        $pickListIndex = (int)$parts[0];
        $itemIndex = (int)$parts[1];
        
        $items = $this->getTableData();
        $item = collect($items)->firstWhere('id', $itemId);
        if (!$item) {
            return;
        }
        
        $shipmentItemIndex = $item['shipment_item_index'] ?? null;
        $quantityRemaining = $item['quantity_remaining'] ?? 0;
        
        if ($shipmentItemIndex === null || $quantityRemaining <= 0) {
            Notification::make()
                ->title('Cannot Mark as Picked')
                ->body('Item cannot be marked as picked.')
                ->warning()
                ->send();
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        if (!isset($pickLists[$pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$pickListIndex];
        if (!isset($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        $pickList['picked_items'][] = [
            'item_index' => $itemIndex,
            'shipment_item_index' => $shipmentItemIndex,
            'quantity_picked' => $quantityRemaining,
            'picked_at' => now()->toIso8601String(),
        ];
        
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        Notification::make()
            ->title('Item Marked as Picked')
            ->success()
            ->send();
        
        $this->dispatch('$refresh');
    }
    
    public function bulkMarkAsPicked(array $itemIds): void
    {
        $count = 0;
        foreach ($itemIds as $itemId) {
            $this->markItemAsPicked($itemId);
            $count++;
        }
        
        Notification::make()
            ->title($count . ' item(s) marked as picked')
            ->success()
            ->send();
        
        $this->selectedItems = [];
        $this->dispatch('$refresh');
    }
    
    public function bulkDeleteItems(array $itemIds): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        $itemsToDelete = [];
        
        foreach ($itemIds as $itemId) {
            $parts = explode('_', $itemId);
            if (count($parts) === 2) {
                $pickListIndex = (int)$parts[0];
                $itemIndex = (int)$parts[1];
                
                if (!isset($itemsToDelete[$pickListIndex])) {
                    $itemsToDelete[$pickListIndex] = [];
                }
                $itemsToDelete[$pickListIndex][] = $itemIndex;
            }
        }
        
        foreach ($itemsToDelete as $pickListIndex => $itemIndices) {
            if (!isset($pickLists[$pickListIndex])) {
                continue;
            }
            
            $pickList = &$pickLists[$pickListIndex];
            $items = $pickList['items'] ?? [];
            
            rsort($itemIndices);
            
            foreach ($itemIndices as $itemIndex) {
                unset($items[$itemIndex]);
            }
            
            $pickList['items'] = array_values($items);
            
            if (isset($pickList['picked_items'])) {
                $pickList['picked_items'] = array_filter(
                    $pickList['picked_items'],
                    fn($picked) => !in_array($picked['item_index'] ?? -1, $itemIndices)
                );
                $pickList['picked_items'] = array_values($pickList['picked_items']);
            }
        }
        
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        Notification::make()
            ->title(count($itemIds) . ' item(s) deleted')
            ->success()
            ->send();
        
        $this->selectedItems = [];
        $this->dispatch('$refresh');
    }
}
