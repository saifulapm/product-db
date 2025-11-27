<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;

class PickListReceivingWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.pick-list-receiving-widget';
    
    protected static ?int $sort = 0;
    
    protected int | string | array $columnSpan = 'full';
    
    // Make widget lazy to prevent timeout on initial load - but disable polling since we use events
    protected static bool $isLazy = false;
    
    public ?IncomingShipment $shipment = null;
    
    // Cache the receiving data to avoid recalculating on every render
    protected ?array $cachedReceivingData = null;
    
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
            $this->shipment = IncomingShipment::find($recordId);
            if ($this->shipment) {
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
        $this->cachedReceivingData = null; // Clear cache on refresh
        $this->shipment = null; // Force reload
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
    
    public function getReceivingData(): array
    {
        // Always reload shipment to get latest data (don't use cache for now)
        $this->loadShipment();
        
        if (!$this->shipment || empty($this->shipment->items)) {
            \Log::info('PickListReceivingWidget: No shipment or items', [
                'has_shipment' => !is_null($this->shipment),
                'has_items' => !empty($this->shipment->items ?? []),
            ]);
            return [];
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        \Log::info('PickListReceivingWidget: Loaded pick lists', [
            'shipment_id' => $this->shipment->id,
            'pick_lists_count' => count($pickLists),
            'pick_lists' => $pickLists,
        ]);
        
        if (!is_array($pickLists) || empty($pickLists)) {
            return [];
        }
        
        // Pre-process pick list items for faster lookup
        $pickListItemsByKey = [];
        $pickedItemsByKey = [];
        
        foreach ($pickLists as $pickListIndex => $pickList) {
            $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
            $pickListItems = $pickList['items'] ?? [];
            $pickedItems = $pickList['picked_items'] ?? [];
            
            foreach ($pickListItems as $itemIndex => $item) {
                // Parse item once
                if (isset($item['description'])) {
                    $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                    $itemStyle = $parsed['style'] ?? '';
                    $itemColor = $parsed['color'] ?? '';
                    $itemPackingWay = $parsed['packing_way'] ?? 'Hook';
                    $quantityNeeded = $item['quantity_required'] ?? $item['quantity'] ?? 0;
                } else {
                    $itemStyle = $item['style'] ?? '';
                    $itemColor = $item['color'] ?? '';
                    $itemPackingWay = $item['packing_way'] ?? 'Hook';
                    $quantityNeeded = $item['quantity'] ?? 0;
                }
                
                // Normalize and create lookup key
                $itemNormalizedStyle = strtolower(trim($itemStyle));
                $itemNormalizedColor = trim(strtolower(trim($itemColor)), ' -');
                $itemNormalizedPackingWay = strtolower(trim($itemPackingWay));
                
                if (strpos($itemNormalizedPackingWay, 'hook') !== false) {
                    $itemNormalizedPackingWay = 'Hook';
                } elseif (strpos($itemNormalizedPackingWay, 'sleeve') !== false || strpos($itemNormalizedPackingWay, 'wrap') !== false) {
                    $itemNormalizedPackingWay = 'sleeve wrap';
                }
                
                $key = $itemNormalizedStyle . '|' . $itemNormalizedColor . '|' . $itemNormalizedPackingWay;
                
                if (!isset($pickListItemsByKey[$key])) {
                    $pickListItemsByKey[$key] = [];
                }
                
                $pickListItemsByKey[$key][] = [
                    'pick_list_index' => $pickListIndex,
                    'pick_list_name' => $pickListName,
                    'item_index' => $itemIndex,
                    'quantity_needed' => $quantityNeeded,
                    'style' => $itemStyle,
                    'color' => $itemColor,
                    'packing_way' => $itemPackingWay,
                ];
            }
            
            // Index picked items for faster lookup
            foreach ($pickedItems as $picked) {
                $pickedKey = ($picked['item_index'] ?? null) . '_' . ($picked['shipment_item_index'] ?? null);
                if (!isset($pickedItemsByKey[$pickListIndex])) {
                    $pickedItemsByKey[$pickListIndex] = [];
                }
                if (!isset($pickedItemsByKey[$pickListIndex][$pickedKey])) {
                    $pickedItemsByKey[$pickListIndex][$pickedKey] = 0;
                }
                $pickedItemsByKey[$pickListIndex][$pickedKey] += $picked['quantity_picked'] ?? 0;
            }
        }
        
        $receivingData = [];
        
        // Process each shipment item (now much faster with pre-indexed data)
        foreach ($this->shipment->items as $shipmentItemIndex => $shipmentItem) {
            $style = $shipmentItem['style'] ?? '';
            $color = $shipmentItem['color'] ?? '';
            $packingWay = $shipmentItem['packing_way'] ?? '';
            $cartonNumber = $shipmentItem['carton_number'] ?? null;
            $shipmentQuantity = $shipmentItem['quantity'] ?? 0;
            
            // Normalize for matching
            $normalizedStyle = strtolower(trim($style));
            $normalizedColor = trim(strtolower(trim($color)), ' -');
            $normalizedPackingWay = strtolower(trim($packingWay));
            
            if (strpos($normalizedPackingWay, 'hook') !== false) {
                $normalizedPackingWay = 'Hook';
            } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                $normalizedPackingWay = 'sleeve wrap';
            }
            
            $key = $normalizedStyle . '|' . $normalizedColor . '|' . $normalizedPackingWay;
            
            // Fast lookup instead of nested loops
            if (!isset($pickListItemsByKey[$key])) {
                continue;
            }
            
            $pickListRequirements = [];
            $totalNeeded = 0;
            $totalPicked = 0;
            
            foreach ($pickListItemsByKey[$key] as $pickListItem) {
                $pickListIndex = $pickListItem['pick_list_index'];
                $itemIndex = $pickListItem['item_index'];
                
                // Fast lookup for picked quantity
                $pickedKey = $itemIndex . '_' . $shipmentItemIndex;
                $quantityPicked = $pickedItemsByKey[$pickListIndex][$pickedKey] ?? 0;
                
                $quantityNeeded = $pickListItem['quantity_needed'];
                $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
                
                if ($quantityRemaining > 0) {
                    $pickListRequirements[] = [
                        'pick_list_index' => $pickListIndex,
                        'pick_list_name' => $pickListItem['pick_list_name'],
                        'item_index' => $itemIndex,
                        'quantity_needed' => $quantityNeeded,
                        'quantity_picked' => $quantityPicked,
                        'quantity_remaining' => $quantityRemaining,
                    ];
                    
                    $totalNeeded += $quantityRemaining;
                }
                
                $totalPicked += $quantityPicked;
            }
            
            // Only include items that are needed by pick lists
            if (!empty($pickListRequirements)) {
                $availableQuantity = $shipmentQuantity - $totalPicked;
                
                $receivingData[] = [
                    'shipment_item_index' => $shipmentItemIndex,
                    'style' => $style,
                    'color' => $color,
                    'packing_way' => $packingWay,
                    'carton_number' => $cartonNumber,
                    'shipment_quantity' => $shipmentQuantity,
                    'available_quantity' => max(0, $availableQuantity),
                    'total_needed' => $totalNeeded,
                    'total_picked' => $totalPicked,
                    'pick_list_requirements' => $pickListRequirements,
                    'can_fulfill' => $availableQuantity >= $totalNeeded,
                ];
            }
        }
        
        // Cache the result
        $this->cachedReceivingData = $receivingData;
        
        return $receivingData;
    }
    
    public function markAsPicked(int $shipmentItemIndex, array $pickListItems): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        $markedCount = 0;
        
        foreach ($pickListItems as $pickListItem) {
            $pickListIndex = $pickListItem['pick_list_index'] ?? null;
            $itemIndex = $pickListItem['item_index'] ?? null;
            $quantityToPick = $pickListItem['quantity'] ?? 0;
            
            if ($pickListIndex === null || $itemIndex === null || $quantityToPick <= 0) {
                continue;
            }
            
            if (!isset($pickLists[$pickListIndex])) {
                continue;
            }
            
            $pickList = &$pickLists[$pickListIndex];
            if (!isset($pickList['picked_items'])) {
                $pickList['picked_items'] = [];
            }
            
            // Check if already picked
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
                    'picked_at' => now()->toIso8601String(),
                ];
            }
            
            $markedCount++;
        }
        
        if ($markedCount > 0) {
            $this->shipment->pick_lists = $pickLists;
            $this->shipment->save();
            
            Notification::make()
                ->title('Items marked as picked')
                ->body($markedCount . ' item(s) marked as picked successfully.')
                ->success()
                ->send();
            
            $this->dispatch('pick-list-updated');
        }
    }
}

