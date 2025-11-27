<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingShipment extends Model
{
    protected $fillable = [
        'name',
        'tracking_number',
        'carrier',
        'supplier',
        'expected_date',
        'received_date',
        'status',
        'items',
        'pick_lists',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'received_date' => 'date',
        'items' => 'array',
        'pick_lists' => 'array',
    ];

    /**
     * Get the user who created this shipment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get orders associated with this shipment.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get order items allocated from this shipment.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get available quantity for a specific item (by style, color, packing_way).
     */
    public function getAvailableQuantity(string $style, string $color, string $packingWay): int
    {
        if (empty($this->items) || !is_array($this->items)) {
            return 0;
        }

        $totalInShipment = 0;
        foreach ($this->items as $item) {
            if (($item['style'] ?? '') === $style &&
                ($item['color'] ?? '') === $color &&
                ($item['packing_way'] ?? '') === $packingWay) {
                $totalInShipment += $item['quantity'] ?? 0;
            }
        }

        // Subtract allocated quantities
        $allocated = $this->orderItems()
            ->where('style', $style)
            ->where('color', $color)
            ->where('packing_way', $packingWay)
            ->sum('quantity_allocated');

        return max(0, $totalInShipment - $allocated);
    }

    /**
     * Get picked quantities from pick lists by item index.
     * This is called from the ViewIncomingShipment page component.
     */
    public function getPickedQuantitiesByIndex(array $pickLists = []): array
    {
        $pickedByIndex = [];
        
        if (empty($pickLists) || !is_array($pickLists)) {
            return $pickedByIndex;
        }
        
        // Iterate through all pick lists and their picked items
        foreach ($pickLists as $pickList) {
            $pickedItems = $pickList['picked_items'] ?? [];
            if (empty($pickedItems) || !is_array($pickedItems)) {
                continue;
            }
            
            foreach ($pickedItems as $pickedItem) {
                $itemIndex = $pickedItem['shipment_item_index'] ?? null;
                $quantityPicked = $pickedItem['quantity_picked'] ?? 0;
                
                if ($itemIndex !== null && $quantityPicked > 0) {
                    if (!isset($pickedByIndex[$itemIndex])) {
                        $pickedByIndex[$itemIndex] = 0;
                    }
                    $pickedByIndex[$itemIndex] += $quantityPicked;
                }
            }
        }
        
        return $pickedByIndex;
    }

    /**
     * Get order allocations per packing list item.
     * Returns array keyed by shipment_item_index with order allocations.
     * Format: [index => [['order_number' => 'BDR1399', 'quantity' => 50], ...]]
     */
    public function getOrderAllocationsByItem(array $pickLists = []): array
    {
        $allocationsByItem = [];
        
        if (empty($pickLists) || !is_array($pickLists)) {
            return $allocationsByItem;
        }
        
        if (empty($this->items) || !is_array($this->items)) {
            return $allocationsByItem;
        }
        
        // Extract order numbers from pick list names
        // Format: "Order BDR1399" -> "BDR1399", "BDR1399" -> "BDR1399"
        $extractOrderNumber = function($pickListName) {
            // Remove "Order" prefix if present
            $name = trim($pickListName);
            $name = preg_replace('/^order\s+/i', '', $name);
            return trim($name);
        };
        
        // Normalize strings for comparison
        $normalize = function($str) {
            return strtolower(trim($str));
        };
        
        // Normalize packing way variations
        $normalizePackingWay = function($packingWay) {
            $normalized = strtolower(trim($packingWay));
            if ($normalized === 'hook' || $normalized === 'sleeve wrap') {
                return $normalized;
            }
            if (strpos($normalized, 'hook') !== false) {
                return 'hook';
            }
            if (strpos($normalized, 'sleeve') !== false || strpos($normalized, 'wrap') !== false) {
                return 'sleeve wrap';
            }
            return $normalized;
        };
        
        // Build lookup table for packing list items by style/color/packing way
        foreach ($pickLists as $pickListIndex => $pickList) {
            $orderNumber = $extractOrderNumber($pickList['name'] ?? '');
            if (empty($orderNumber)) {
                continue;
            }
            
            $pickListItems = $pickList['items'] ?? [];
            
            foreach ($pickListItems as $pickListItem) {
                $style = $normalize($pickListItem['style'] ?? '');
                $color = $normalize($pickListItem['color'] ?? '');
                $packingWay = $normalizePackingWay($pickListItem['packing_way'] ?? '');
                $quantityNeeded = $pickListItem['quantity'] ?? $pickListItem['quantity_required'] ?? 0;
                
                if (empty($style) && empty($color)) {
                    continue;
                }
                
                // Find matching packing list items
                foreach ($this->items as $shipmentItemIndex => $shipmentItem) {
                    $shipmentStyle = $normalize($shipmentItem['style'] ?? '');
                    $shipmentColor = $normalize($shipmentItem['color'] ?? '');
                    $shipmentPackingWay = $normalizePackingWay($shipmentItem['packing_way'] ?? '');
                    
                    // Match by style and color (packing way is flexible)
                    $styleMatch = empty($style) || empty($shipmentStyle) || $shipmentStyle === $style || strpos($shipmentStyle, $style) !== false || strpos($style, $shipmentStyle) !== false;
                    $colorMatch = empty($color) || empty($shipmentColor) || $shipmentColor === $color || strpos($shipmentColor, $color) !== false || strpos($color, $shipmentColor) !== false;
                    
                    if ($styleMatch && $colorMatch) {
                        if (!isset($allocationsByItem[$shipmentItemIndex])) {
                            $allocationsByItem[$shipmentItemIndex] = [];
                        }
                        
                        // Check if this order already has an allocation for this item
                        $found = false;
                        foreach ($allocationsByItem[$shipmentItemIndex] as &$allocation) {
                            if ($allocation['order_number'] === $orderNumber) {
                                $allocation['quantity'] += $quantityNeeded;
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $allocationsByItem[$shipmentItemIndex][] = [
                                'order_number' => $orderNumber,
                                'quantity' => $quantityNeeded,
                            ];
                        }
                    }
                }
            }
        }
        
        return $allocationsByItem;
    }

    /**
     * Get available quantities grouped by carton/item index.
     * Returns array with item index, carton, style, color, packing_way, original qty, allocated qty, picked qty, available qty
     */
    public function getAvailableQuantitiesByCarton(array $pickLists = []): array
    {
        if (empty($this->items) || !is_array($this->items)) {
            return [];
        }

        $result = [];
        
        // Load all allocations grouped by shipment_item_index for accurate tracking
        $allocationsByIndex = $this->orderItems()
            ->selectRaw('shipment_item_index, SUM(quantity_allocated) as total_allocated')
            ->whereNotNull('shipment_item_index')
            ->groupBy('shipment_item_index')
            ->get()
            ->keyBy('shipment_item_index');

        // Get picked quantities from pick lists
        $pickedByIndex = $this->getPickedQuantitiesByIndex($pickLists);

        foreach ($this->items as $index => $item) {
            $style = $item['style'] ?? '';
            $color = $item['color'] ?? '';
            $packingWay = $item['packing_way'] ?? '';
            $originalQty = $item['quantity'] ?? 0;
            
            // Get allocated quantity for this specific item index
            $allocatedQty = $allocationsByIndex->get($index)->total_allocated ?? 0;
            
            // Get picked quantity for this specific item index
            $pickedQty = $pickedByIndex[$index] ?? 0;
            
            // Available = original - allocated - picked
            $availableQty = max(0, $originalQty - $allocatedQty - $pickedQty);
            
            $result[] = [
                'index' => $index,
                'carton_number' => $item['carton_number'] ?? '',
                'style' => $style,
                'color' => $color,
                'packing_way' => $packingWay,
                'original_quantity' => $originalQty,
                'allocated_quantity' => $allocatedQty,
                'picked_quantity' => $pickedQty,
                'available_quantity' => $availableQty,
            ];
        }

        return $result;
    }

    /**
     * Get cartons that have available stock for a specific item.
     * Returns only cartons with available quantity > 0, sorted by carton number.
     */
    public function getAvailableCartonsForItem(string $style, string $color, string $packingWay, int $quantityNeeded, array $pickLists = []): array
    {
        $availableItems = $this->getAvailableQuantitiesByCarton($pickLists);
        $matchingCartons = [];
        $totalAvailable = 0;
        
        // Normalize inputs for comparison
        $normalizedStyle = strtolower(trim($style));
        $normalizedColor = strtolower(trim($color));
        $normalizedPackingWay = strtolower(trim($packingWay));
        
        // Normalize packing way variations
        if ($normalizedPackingWay === 'hook' || $normalizedPackingWay === 'sleeve wrap') {
            // Already normalized
        } elseif (strpos($normalizedPackingWay, 'hook') !== false) {
            $normalizedPackingWay = 'hook';
        } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
            $normalizedPackingWay = 'sleeve wrap';
        }
        
        foreach ($availableItems as $item) {
            $itemStyle = strtolower(trim($item['style'] ?? ''));
            $itemColor = strtolower(trim($item['color'] ?? ''));
            $itemPackingWay = strtolower(trim($item['packing_way'] ?? ''));
            
            // Normalize item packing way
            if (strpos($itemPackingWay, 'hook') !== false) {
                $itemPackingWay = 'hook';
            } elseif (strpos($itemPackingWay, 'sleeve') !== false || strpos($itemPackingWay, 'wrap') !== false) {
                $itemPackingWay = 'sleeve wrap';
            }
            
            // Clean up color - remove trailing dashes
            $itemColor = trim($itemColor, ' -');
            $normalizedColor = trim($normalizedColor, ' -');
            
            // Match with flexible comparison
            $styleMatch = $itemStyle === $normalizedStyle || 
                         (strpos($itemStyle, $normalizedStyle) !== false || strpos($normalizedStyle, $itemStyle) !== false);
            $colorMatch = $itemColor === $normalizedColor || 
                         (strpos($itemColor, $normalizedColor) !== false || strpos($normalizedColor, $itemColor) !== false);
            $packingMatch = $itemPackingWay === $normalizedPackingWay;
            
            // Only include cartons with available quantity > 0
            if ($styleMatch && $colorMatch && $packingMatch && ($item['available_quantity'] ?? 0) > 0) {
                $matchingCartons[] = [
                    'carton_number' => $item['carton_number'],
                    'available_quantity' => $item['available_quantity'],
                    'shipment_item_index' => $item['index'],
                ];
                $totalAvailable += $item['available_quantity'];
            }
        }
        
        // Sort by carton number for consistent picking (lowest carton number first)
        usort($matchingCartons, function($a, $b) {
            // Try numeric comparison first
            if (is_numeric($a['carton_number']) && is_numeric($b['carton_number'])) {
                return (int)$a['carton_number'] <=> (int)$b['carton_number'];
            }
            // Fallback to string comparison
            return strcmp($a['carton_number'], $b['carton_number']);
        });

        return [
            'cartons' => $matchingCartons, // Only cartons with available stock
            'total_available' => $totalAvailable,
            'can_fulfill' => $totalAvailable >= $quantityNeeded,
        ];
    }
}


