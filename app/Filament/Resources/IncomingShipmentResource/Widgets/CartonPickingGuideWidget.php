<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Widgets\Widget;

class CartonPickingGuideWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.carton-picking-guide-widget';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?IncomingShipment $shipment = null;
    
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
        // Force reload shipment data
        $this->shipment = null; // Force reload
        $this->loadShipment();
        $this->dispatch('$refresh');
    }
    
    public function getCartonPickingData(): array
    {
        $this->loadShipment();
        
        if (!$this->shipment) {
            \Log::info('CartonPickingGuideWidget: No shipment');
            return [];
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        \Log::info('CartonPickingGuideWidget: Loaded pick lists', [
            'shipment_id' => $this->shipment->id,
            'pick_lists_count' => count($pickLists),
            'pick_lists' => $pickLists,
        ]);
        
        if (!is_array($pickLists) || empty($pickLists)) {
            return [];
        }
        
        // Get all cartons from shipment items
        $allCartons = [];
        if (!empty($this->shipment->items) && is_array($this->shipment->items)) {
            foreach ($this->shipment->items as $itemIndex => $item) {
                $cartonNumber = $item['carton_number'] ?? null;
                if ($cartonNumber !== null) {
                    if (!isset($allCartons[$cartonNumber])) {
                        $allCartons[$cartonNumber] = [
                            'carton_number' => $cartonNumber,
                            'items' => [],
                            'orders_needing' => [],
                        ];
                    }
                    
                    $allCartons[$cartonNumber]['items'][] = [
                        'item_index' => $itemIndex,
                        'style' => $item['style'] ?? '',
                        'color' => $item['color'] ?? '',
                        'packing_way' => $item['packing_way'] ?? '',
                        'quantity' => $item['quantity'] ?? 0,
                    ];
                }
            }
        }
        
        // Process each pick list to see which orders need items from which cartons
        foreach ($pickLists as $pickListIndex => $pickList) {
            $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
            $pickListItems = $pickList['items'] ?? [];
            $pickedItems = $pickList['picked_items'] ?? [];
            
            foreach ($pickListItems as $itemIndex => $item) {
                // Parse item
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
                
                // Calculate picked quantity
                $quantityPicked = 0;
                foreach ($pickedItems as $picked) {
                    if (($picked['item_index'] ?? null) === $itemIndex) {
                        $quantityPicked += $picked['quantity_picked'] ?? 0;
                    }
                }
                
                $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
                
                if ($quantityRemaining <= 0) {
                    continue; // Already fully picked
                }
                
                // Find which cartons have this item
                $availableCartons = $this->shipment->getAvailableCartonsForItem($style, $color, $packingWay, $quantityRemaining, [$pickList]);
                
                if (!empty($availableCartons['cartons'])) {
                    foreach ($availableCartons['cartons'] as $cartonInfo) {
                        $cartonNum = $cartonInfo['carton_number'] ?? null;
                        if ($cartonNum !== null) {
                            if (!isset($allCartons[$cartonNum])) {
                                $allCartons[$cartonNum] = [
                                    'carton_number' => $cartonNum,
                                    'items' => [],
                                    'orders_needing' => [],
                                ];
                            }
                            
                            // Add order to carton's orders_needing if not already there
                            $orderKey = $pickListIndex . '_' . $pickListName;
                            if (!isset($allCartons[$cartonNum]['orders_needing'][$orderKey])) {
                                $allCartons[$cartonNum]['orders_needing'][$orderKey] = [
                                    'pick_list_index' => $pickListIndex,
                                    'pick_list_name' => $pickListName,
                                    'items' => [],
                                ];
                            }
                            
                            $allCartons[$cartonNum]['orders_needing'][$orderKey]['items'][] = [
                                'style' => $style,
                                'color' => $color,
                                'packing_way' => $packingWay,
                                'quantity_needed' => $quantityNeeded,
                                'quantity_remaining' => $quantityRemaining,
                                'available_in_carton' => $cartonInfo['available_quantity'] ?? 0,
                            ];
                        }
                    }
                }
            }
        }
        
        // Sort cartons numerically
        ksort($allCartons, SORT_NUMERIC);
        
        return array_values($allCartons);
    }
}

