<?php

namespace App\Filament\Resources\PackingListResource\Pages;

use App\Filament\Resources\PackingListResource;
use App\Filament\Resources\IncomingShipmentResource;
use App\Filament\Resources\PackingListResource\Widgets\PickListHistoryWidget;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewPackingList extends Page
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.pages.view-pick-list';
    
    protected static string $resource = PackingListResource::class;
    
    public $shipmentId;
    public $pickListIndex;
    public $pickList = [];
    public $shipment = null;
    public $selectedPickListItems = [];
    
    public function mount(int | string $shipmentId, int $pickListIndex)
    {
        $this->shipmentId = $shipmentId;
        $this->pickListIndex = $pickListIndex;
        
        $this->shipment = \App\Models\IncomingShipment::find($shipmentId);
        
        if (!$this->shipment) {
            Notification::make()
                ->title('Shipment not found')
                ->danger()
                ->send();
            
            return redirect(PackingListResource::getUrl('index'));
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$pickListIndex])) {
            Notification::make()
                ->title('Pick list not found')
                ->danger()
                ->send();
            
            return redirect(PackingListResource::getUrl('index'));
        }
        
        $this->pickList = $pickLists[$pickListIndex];
    }
    
    public function getTitle(): string
    {
        return $this->pickList['name'] ?? 'Pick List';
    }
    
    public function getHeading(): string
    {
        return $this->pickList['name'] ?? 'Pick List';
    }
    
    public function getSubheading(): ?string
    {
        $filename = $this->pickList['filename'] ?? '';
        $uploadedAt = $this->pickList['uploaded_at'] ?? '';
        
        if ($uploadedAt) {
            try {
                $date = \Carbon\Carbon::parse($uploadedAt);
                $uploadedAt = $date->format('M d, Y g:i A');
            } catch (\Exception $e) {
                // Keep original format if parsing fails
            }
        }
        
        return $filename ? "File: {$filename}" . ($uploadedAt ? " • Uploaded: {$uploadedAt}" : '') : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Packing Lists')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => PackingListResource::getUrl('index')),
            Actions\Action::make('view_shipment')
                ->label('View Shipment')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->url(fn () => IncomingShipmentResource::getUrl('edit', ['record' => $this->shipmentId])),
            Actions\Action::make('delete_pick_list')
                ->label('Delete Pick List')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Pick List')
                ->modalDescription('Are you sure you want to delete this pick list? This action cannot be undone.')
                ->action(function () {
                    $pickLists = $this->shipment->pick_lists ?? [];
                    
                    if (isset($pickLists[$this->pickListIndex])) {
                        unset($pickLists[$this->pickListIndex]);
                        $pickLists = array_values($pickLists); // Re-index array
                        
                        $this->shipment->pick_lists = $pickLists;
                        $this->shipment->save();
                        
                        Notification::make()
                            ->title('Pick list deleted')
                            ->success()
                            ->send();
                        
                        return redirect(PackingListResource::getUrl('index'));
                    }
                }),
        ];
    }
    
    public function markItemAsPicked(int $itemIndex, int $shipmentItemIndex, int $quantityToPick): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        // Get item details first to find matching cartons
        $items = $pickList['items'] ?? [];
        $item = $items[$itemIndex] ?? null;
        $cartonNumber = '';
        
        if ($item) {
            // Parse item details
            if (isset($item['description'])) {
                $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                $style = $parsed['style'] ?? '';
                $color = $parsed['color'] ?? '';
                $packingWay = $parsed['packing_way'] ?? 'hook';
            } else {
                $style = $item['style'] ?? '';
                $color = $item['color'] ?? '';
                $packingWay = $item['packing_way'] ?? 'hook';
            }
            
            // Normalize for matching
            $normalizedStyle = strtolower(trim($style));
            $normalizedColor = trim(strtolower(trim($color)), ' -');
            $normalizedPackingWay = strtolower(trim($packingWay));
            if (strpos($normalizedPackingWay, 'hook') !== false) {
                $normalizedPackingWay = 'hook';
            } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                $normalizedPackingWay = 'sleeve wrap';
            }
            
            // First, try to get carton number from the shipment item index
            $shipmentItems = $this->shipment->items ?? [];
            if (isset($shipmentItems[$shipmentItemIndex])) {
                $shipmentItem = $shipmentItems[$shipmentItemIndex];
                $cartonNumber = $shipmentItem['carton_number'] ?? '';
                // Convert to string if it's numeric
                if (is_numeric($cartonNumber)) {
                    $cartonNumber = (string)$cartonNumber;
                }
            }
            
            // If carton number is empty, find it from matching shipment items
            if (empty($cartonNumber)) {
                foreach ($shipmentItems as $idx => $shipItem) {
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
                        $potentialCartonNumber = $shipItem['carton_number'] ?? '';
                        if (!empty($potentialCartonNumber)) {
                            // Convert to string if it's numeric
                            if (is_numeric($potentialCartonNumber)) {
                                $potentialCartonNumber = (string)$potentialCartonNumber;
                            }
                            $cartonNumber = $potentialCartonNumber;
                            break; // Use first matching carton number found
                        }
                    }
                }
            }
            
            // If still empty, get it from available cartons (this matches what's shown in UI)
            if (empty($cartonNumber)) {
                $availableCartons = $this->shipment->getAvailableCartonsForItem($style, $color, $packingWay, $quantityToPick, [$pickList]);
                if (!empty($availableCartons['cartons']) && isset($availableCartons['cartons'][0])) {
                    $cartonNumber = $availableCartons['cartons'][0]['carton_number'] ?? '';
                    // Convert to string if it's numeric
                    if (is_numeric($cartonNumber)) {
                        $cartonNumber = (string)$cartonNumber;
                    }
                }
            }
        }
        
        // Check if already picked
        $found = false;
        foreach ($pickList['picked_items'] as &$picked) {
            if (($picked['item_index'] ?? null) === $itemIndex && 
                ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex &&
                ($picked['carton_number'] ?? '') === $cartonNumber) {
                $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityToPick;
                $found = true;
                break;
            }
        }
        
        // Add history entry
        $user = Auth::user();
        if (!isset($pickList['history'])) {
            $pickList['history'] = [];
        }
        
        // Get item details for history
        $items = $pickList['items'] ?? [];
        $item = $items[$itemIndex] ?? null;
        $itemDescription = '';
        if ($item) {
            if (isset($item['description'])) {
                $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                $style = $parsed['style'] ?? '';
                $color = $parsed['color'] ?? '';
                $packingWay = $parsed['packing_way'] ?? 'hook';
            } else {
                $style = $item['style'] ?? '';
                $color = $item['color'] ?? '';
                $packingWay = $item['packing_way'] ?? 'hook';
            }
            $itemDescription = trim(($style ?: '') . ' - ' . ($color ?: '') . ' - ' . ($packingWay ?: 'hook'));
        }
        
        // Ensure carton number is captured - log if empty for debugging
        if (empty($cartonNumber)) {
            \Log::warning('Carton number is empty when picking item', [
                'item_index' => $itemIndex,
                'shipment_item_index' => $shipmentItemIndex,
                'style' => $style ?? '',
                'color' => $color ?? '',
                'packing_way' => $packingWay ?? '',
            ]);
        }
        
        $pickList['history'][] = [
            'action' => 'picked',
            'item_index' => $itemIndex,
            'item_description' => $itemDescription,
            'quantity' => $quantityToPick,
            'carton_number' => $cartonNumber ?: '', // Ensure it's always a string
            'action_at' => now()->toIso8601String(),
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
        ];
        
        if (!$found) {
            $pickList['picked_items'][] = [
                'item_index' => $itemIndex,
                'shipment_item_index' => $shipmentItemIndex,
                'carton_number' => $cartonNumber,
                'quantity_picked' => $quantityToPick,
                'picked_at' => now()->toIso8601String(),
                'picked_by_user_id' => $user ? $user->id : null,
                'picked_by_user_name' => $user ? $user->name : 'System',
            ];
        } else {
            // Update existing picked item with user info if not set
            foreach ($pickList['picked_items'] as &$picked) {
                if (($picked['item_index'] ?? null) === $itemIndex && 
                    ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex &&
                    ($picked['carton_number'] ?? '') === $cartonNumber) {
                    if (!isset($picked['picked_by_user_id'])) {
                        $picked['picked_by_user_id'] = $user ? $user->id : null;
                        $picked['picked_by_user_name'] = $user ? $user->name : 'System';
                    }
                    break;
                }
            }
        }
        
        // Update status based on picked quantities
        $items = $pickList['items'] ?? [];
        $totalNeeded = 0;
        foreach ($items as $item) {
            $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
        }
        $totalPicked = 0;
        foreach ($pickList['picked_items'] as $picked) {
            $totalPicked += $picked['quantity_picked'] ?? 0;
        }
        
        if ($totalNeeded > 0) {
            if ($totalPicked >= $totalNeeded) {
                $pickList['status'] = 'picked';
            } elseif ($totalPicked > 0) {
                $pickList['status'] = 'partially_picked';
            } else {
                $pickList['status'] = 'pending';
            }
        }
        
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        // Reload pick list
        $this->pickList = $pickLists[$this->pickListIndex];
        
        Notification::make()
            ->title('Item marked as picked')
            ->success()
            ->send();
    }
    
    public function bulkMarkItemsAsPicked(array $itemData): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            $pickList['picked_items'] = [];
        }
        
        $markedCount = 0;
        $user = Auth::user();
        $items = $pickList['items'] ?? [];
        
        if (!isset($pickList['history'])) {
            $pickList['history'] = [];
        }
        
        foreach ($itemData as $data) {
            $itemIndex = $data['itemIndex'] ?? null;
            $shipmentItemIndex = $data['shipmentItemIndex'] ?? null;
            $quantityToPick = $data['quantity'] ?? 0;
            
            if ($itemIndex === null || $shipmentItemIndex === null || $quantityToPick <= 0) {
                continue;
            }
            
            // Get item details first to find matching cartons
            $item = $items[$itemIndex] ?? null;
            $cartonNumber = '';
            
            if ($item) {
                // Parse item details
                if (isset($item['description'])) {
                    $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                    $style = $parsed['style'] ?? '';
                    $color = $parsed['color'] ?? '';
                    $packingWay = $parsed['packing_way'] ?? 'hook';
                } else {
                    $style = $item['style'] ?? '';
                    $color = $item['color'] ?? '';
                    $packingWay = $item['packing_way'] ?? 'hook';
                }
                
                // Normalize for matching
                $normalizedStyle = strtolower(trim($style));
                $normalizedColor = trim(strtolower(trim($color)), ' -');
                $normalizedPackingWay = strtolower(trim($packingWay));
                if (strpos($normalizedPackingWay, 'hook') !== false) {
                    $normalizedPackingWay = 'hook';
                } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                    $normalizedPackingWay = 'sleeve wrap';
                }
                
            // Get carton number - prioritize finding ANY carton number from matching items
            $shipmentItems = $this->shipment->items ?? [];
            
            // Strategy 1: Try exact shipment item index first (only if it has a carton number)
            if (isset($shipmentItems[$shipmentItemIndex])) {
                $shipmentItem = $shipmentItems[$shipmentItemIndex];
                $potentialCarton = $shipmentItem['carton_number'] ?? '';
                if (!empty($potentialCarton) && $potentialCarton !== '0') {
                    $cartonNumber = is_numeric($potentialCarton) ? (string)$potentialCarton : $potentialCarton;
                }
            }
            
            // Strategy 2: Find carton number from ANY matching shipment item (same style, color, packing way)
            // This will find carton numbers even if the exact index doesn't have one
            foreach ($shipmentItems as $idx => $shipItem) {
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
                    $potentialCarton = $shipItem['carton_number'] ?? '';
                    if (!empty($potentialCarton) && $potentialCarton !== '0') {
                        $cartonNumber = is_numeric($potentialCarton) ? (string)$potentialCarton : $potentialCarton;
                        break; // Found a carton number, use it
                    }
                }
            }
            
            // Strategy 3: Match by style and color only (ignore packing way) - broader match
            if (empty($cartonNumber)) {
                foreach ($shipmentItems as $idx => $shipItem) {
                    $shipStyle = strtolower(trim($shipItem['style'] ?? ''));
                    $shipColor = trim(strtolower(trim($shipItem['color'] ?? '')), ' -');
                    
                    $styleMatch = $shipStyle === $normalizedStyle || 
                                 (strpos($shipStyle, $normalizedStyle) !== false || strpos($normalizedStyle, $shipStyle) !== false);
                    $colorMatch = $shipColor === $normalizedColor || 
                                 (strpos($shipColor, $normalizedColor) !== false || strpos($normalizedColor, $shipColor) !== false);
                    
                    if ($styleMatch && $colorMatch) {
                        $potentialCarton = $shipItem['carton_number'] ?? '';
                        if (!empty($potentialCarton) && $potentialCarton !== '0') {
                            $cartonNumber = is_numeric($potentialCarton) ? (string)$potentialCarton : $potentialCarton;
                            break; // Found a carton number, use it
                        }
                    }
                }
            }
            
            // Strategy 4: Get from available cartons method (last resort)
            if (empty($cartonNumber)) {
                $availableCartons = $this->shipment->getAvailableCartonsForItem($style, $color, $packingWay, $quantityToPick, [$pickList]);
                if (!empty($availableCartons['cartons'])) {
                    // Try to find first carton with a carton number
                    foreach ($availableCartons['cartons'] as $carton) {
                        $potentialCarton = $carton['carton_number'] ?? '';
                        if (!empty($potentialCarton) && $potentialCarton !== '0') {
                            $cartonNumber = is_numeric($potentialCarton) ? (string)$potentialCarton : $potentialCarton;
                            break;
                        }
                    }
                }
            }
            }
            
            // Get item details for history
            $item = $items[$itemIndex] ?? null;
            $itemDescription = '';
            if ($item) {
                if (isset($item['description'])) {
                    $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                    $style = $parsed['style'] ?? '';
                    $color = $parsed['color'] ?? '';
                    $packingWay = $parsed['packing_way'] ?? 'hook';
                } else {
                    $style = $item['style'] ?? '';
                    $color = $item['color'] ?? '';
                    $packingWay = $item['packing_way'] ?? 'hook';
                }
                $itemDescription = trim(($style ?: '') . ' - ' . ($color ?: '') . ' - ' . ($packingWay ?: 'hook'));
            }
            
            // Add history entry
            $pickList['history'][] = [
                'action' => 'picked',
                'item_index' => $itemIndex,
                'item_description' => $itemDescription,
                'quantity' => $quantityToPick,
                'carton_number' => $cartonNumber,
                'action_at' => now()->toIso8601String(),
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'System',
            ];
            
            // Check if already picked
            $found = false;
            foreach ($pickList['picked_items'] as &$picked) {
                if (($picked['item_index'] ?? null) === $itemIndex && 
                    ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex &&
                    ($picked['carton_number'] ?? '') === $cartonNumber) {
                    $picked['quantity_picked'] = ($picked['quantity_picked'] ?? 0) + $quantityToPick;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $pickList['picked_items'][] = [
                    'item_index' => $itemIndex,
                    'shipment_item_index' => $shipmentItemIndex,
                    'carton_number' => $cartonNumber,
                    'quantity_picked' => $quantityToPick,
                    'picked_at' => now()->toIso8601String(),
                    'picked_by_user_id' => $user ? $user->id : null,
                    'picked_by_user_name' => $user ? $user->name : 'System',
                ];
            } else {
                // Update existing picked item with user info if not set
                foreach ($pickList['picked_items'] as &$picked) {
                    if (($picked['item_index'] ?? null) === $itemIndex && 
                        ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex &&
                        ($picked['carton_number'] ?? '') === $cartonNumber) {
                        if (!isset($picked['picked_by_user_id'])) {
                            $picked['picked_by_user_id'] = $user ? $user->id : null;
                            $picked['picked_by_user_name'] = $user ? $user->name : 'System';
                        }
                        break;
                    }
                }
            }
            
            $markedCount++;
        }
        
        if ($markedCount > 0) {
            // Update status based on picked quantities
            $items = $pickList['items'] ?? [];
            $totalNeeded = 0;
            foreach ($items as $item) {
                $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
            }
            $totalPicked = 0;
            foreach ($pickList['picked_items'] as $picked) {
                $totalPicked += $picked['quantity_picked'] ?? 0;
            }
            
            if ($totalNeeded > 0) {
                if ($totalPicked >= $totalNeeded) {
                    $pickList['status'] = 'fully_picked';
                } elseif ($totalPicked > 0) {
                    $pickList['status'] = 'partially_picked';
                } else {
                    $pickList['status'] = 'not_picked';
                }
            }
            
            $this->shipment->pick_lists = $pickLists;
            $this->shipment->save();
            
            // Reload pick list
            $this->pickList = $pickLists[$this->pickListIndex];
            
            Notification::make()
                ->title('Items marked as picked')
                ->body($markedCount . ' item(s) marked as picked.')
                ->success()
                ->send();
        }
    }
    
    public function unpickItem(int $itemIndex, int $shipmentItemIndex, int $quantityToUnpick): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            return;
        }
        
        // Get carton number and item details for history
        $cartonNumber = '';
        $items = $pickList['items'] ?? [];
        $item = $items[$itemIndex] ?? null;
        $itemDescription = '';
        
        if ($item) {
            if (isset($item['description'])) {
                $parsed = \App\Models\Order::parseOrderDescription($item['description']);
                $style = $parsed['style'] ?? '';
                $color = $parsed['color'] ?? '';
                $packingWay = $parsed['packing_way'] ?? 'hook';
            } else {
                $style = $item['style'] ?? '';
                $color = $item['color'] ?? '';
                $packingWay = $item['packing_way'] ?? 'hook';
            }
            $itemDescription = trim(($style ?: '') . ' - ' . ($color ?: '') . ' - ' . ($packingWay ?: 'hook'));
        }
        
        // Find and reduce picked quantity
        foreach ($pickList['picked_items'] as &$picked) {
            if (($picked['item_index'] ?? null) === $itemIndex && 
                ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex) {
                $cartonNumber = $picked['carton_number'] ?? '';
                $currentPicked = $picked['quantity_picked'] ?? 0;
                $newPicked = max(0, $currentPicked - $quantityToUnpick);
                
                if ($newPicked > 0) {
                    $picked['quantity_picked'] = $newPicked;
                } else {
                    // Remove the picked item if quantity reaches 0
                    $pickList['picked_items'] = array_values(array_filter($pickList['picked_items'], function ($p) use ($itemIndex, $shipmentItemIndex) {
                        return !(($p['item_index'] ?? null) === $itemIndex && 
                                ($p['shipment_item_index'] ?? null) === $shipmentItemIndex);
                    }));
                }
                break;
            }
        }
        
        // Add history entry
        $user = Auth::user();
        if (!isset($pickList['history'])) {
            $pickList['history'] = [];
        }
        
        $pickList['history'][] = [
            'action' => 'unpicked',
            'item_index' => $itemIndex,
            'item_description' => $itemDescription,
            'quantity' => $quantityToUnpick,
            'carton_number' => $cartonNumber,
            'action_at' => now()->toIso8601String(),
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'System',
        ];
        
        // Update status based on picked quantities
        $items = $pickList['items'] ?? [];
        $totalNeeded = 0;
        foreach ($items as $item) {
            $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
        }
        $totalPicked = 0;
        foreach ($pickList['picked_items'] as $picked) {
            $totalPicked += $picked['quantity_picked'] ?? 0;
        }
        
        if ($totalNeeded > 0) {
            if ($totalPicked >= $totalNeeded) {
                $pickList['status'] = 'picked';
            } elseif ($totalPicked > 0) {
                $pickList['status'] = 'partially_picked';
            } else {
                $pickList['status'] = 'pending';
            }
        }
        
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        // Reload pick list
        $this->pickList = $pickLists[$this->pickListIndex];
        
        Notification::make()
            ->title('Item unpicked')
            ->success()
            ->send();
    }
    
    public function bulkUnpickItems(array $itemData): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        if (!isset($pickList['picked_items'])) {
            return;
        }
        
        $unpickedCount = 0;
        
        foreach ($itemData as $data) {
            $itemIndex = $data['itemIndex'] ?? null;
            $shipmentItemIndex = $data['shipmentItemIndex'] ?? null;
            $quantityToUnpick = $data['quantity'] ?? 0;
            
            if ($itemIndex === null || $shipmentItemIndex === null || $quantityToUnpick <= 0) {
                continue;
            }
            
            // Find and reduce picked quantity
            foreach ($pickList['picked_items'] as &$picked) {
                if (($picked['item_index'] ?? null) === $itemIndex && 
                    ($picked['shipment_item_index'] ?? null) === $shipmentItemIndex) {
                    $currentPicked = $picked['quantity_picked'] ?? 0;
                    $newPicked = max(0, $currentPicked - $quantityToUnpick);
                    
                    if ($newPicked > 0) {
                        $picked['quantity_picked'] = $newPicked;
                    } else {
                        // Remove the picked item if quantity reaches 0
                        $pickList['picked_items'] = array_values(array_filter($pickList['picked_items'], function ($p) use ($itemIndex, $shipmentItemIndex) {
                            return !(($p['item_index'] ?? null) === $itemIndex && 
                                    ($p['shipment_item_index'] ?? null) === $shipmentItemIndex);
                        }));
                    }
                    $unpickedCount++;
                    break;
                }
            }
        }
        
        if ($unpickedCount > 0) {
            // Update status based on picked quantities
            $items = $pickList['items'] ?? [];
            $totalNeeded = 0;
            foreach ($items as $item) {
                $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
            }
            $totalPicked = 0;
            foreach ($pickList['picked_items'] as $picked) {
                $totalPicked += $picked['quantity_picked'] ?? 0;
            }
            
            if ($totalNeeded > 0) {
                if ($totalPicked >= $totalNeeded) {
                    $pickList['status'] = 'fully_picked';
                } elseif ($totalPicked > 0) {
                    $pickList['status'] = 'partially_picked';
                } else {
                    $pickList['status'] = 'not_picked';
                }
            }
            
            $this->shipment->pick_lists = $pickLists;
            $this->shipment->save();
            
            // Reload pick list
            $this->pickList = $pickLists[$this->pickListIndex];
            
            Notification::make()
                ->title('Items unpicked')
                ->body($unpickedCount . ' item(s) unpicked.')
                ->success()
                ->send();
        }
    }
    
    public function bulkDeletePickListItems(array $itemIndices): void
    {
        if (!$this->shipment) {
            return;
        }
        
        $pickLists = $this->shipment->pick_lists ?? [];
        
        if (!isset($pickLists[$this->pickListIndex])) {
            return;
        }
        
        $pickList = &$pickLists[$this->pickListIndex];
        $pickListItems = $pickList['items'] ?? [];
        
        rsort($itemIndices);
        
        $deletedCount = 0;
        foreach ($itemIndices as $itemIndex) {
            if (isset($pickListItems[$itemIndex])) {
                unset($pickListItems[$itemIndex]);
                
                // Remove picked items
                if (isset($pickList['picked_items'])) {
                    $pickList['picked_items'] = array_values(array_filter($pickList['picked_items'], function ($picked) use ($itemIndex) {
                        return ($picked['item_index'] ?? null) !== $itemIndex;
                    }));
                }
                
                $deletedCount++;
            }
        }
        
        $pickList['items'] = array_values($pickListItems);
        $this->shipment->pick_lists = $pickLists;
        $this->shipment->save();
        
        // Reload pick list
        $this->pickList = $pickLists[$this->pickListIndex];
        
        Notification::make()
            ->title('Items deleted')
            ->body($deletedCount . ' item(s) deleted from pick list.')
            ->success()
            ->send();
    }
}
