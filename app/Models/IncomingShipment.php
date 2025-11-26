<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingShipment extends Model
{
    protected $fillable = [
        'tracking_number',
        'carrier',
        'supplier',
        'expected_date',
        'received_date',
        'status',
        'items',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'received_date' => 'date',
        'items' => 'array',
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
     * Get available quantities grouped by carton/item index.
     * Returns array with item index, carton, style, color, packing_way, original qty, allocated qty, available qty
     */
    public function getAvailableQuantitiesByCarton(): array
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

        foreach ($this->items as $index => $item) {
            $style = $item['style'] ?? '';
            $color = $item['color'] ?? '';
            $packingWay = $item['packing_way'] ?? '';
            $originalQty = $item['quantity'] ?? 0;
            
            // Get allocated quantity for this specific item index
            $allocatedQty = $allocationsByIndex->get($index)->total_allocated ?? 0;
            $availableQty = max(0, $originalQty - $allocatedQty);
            
            $result[] = [
                'index' => $index,
                'carton_number' => $item['carton_number'] ?? '',
                'style' => $style,
                'color' => $color,
                'packing_way' => $packingWay,
                'original_quantity' => $originalQty,
                'allocated_quantity' => $allocatedQty,
                'available_quantity' => $availableQty,
            ];
        }

        return $result;
    }

    /**
     * Get cartons that have available stock for a specific item.
     */
    public function getAvailableCartonsForItem(string $style, string $color, string $packingWay, int $quantityNeeded): array
    {
        $availableItems = $this->getAvailableQuantitiesByCarton();
        $matchingCartons = [];
        
        foreach ($availableItems as $item) {
            if (strtolower(trim($item['style'])) === strtolower(trim($style)) &&
                strtolower(trim($item['color'])) === strtolower(trim($color)) &&
                strtolower(trim($item['packing_way'])) === strtolower(trim($packingWay)) &&
                $item['available_quantity'] > 0) {
                $matchingCartons[] = [
                    'carton_number' => $item['carton_number'],
                    'available_quantity' => $item['available_quantity'],
                    'index' => $item['index'],
                ];
            }
        }
        
        return $matchingCartons;
    }
}
