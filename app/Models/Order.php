<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'client_name',
        'incoming_shipment_id',
        'items',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    /**
     * Get the incoming shipment this order is associated with.
     */
    public function incomingShipment(): BelongsTo
    {
        return $this->belongsTo(IncomingShipment::class);
    }

    /**
     * Get the order items (allocations).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user who created this order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get total items required.
     */
    public function getTotalItemsRequiredAttribute(): int
    {
        if (empty($this->items) || !is_array($this->items)) {
            return 0;
        }
        return array_sum(array_column($this->items, 'quantity_required'));
    }

    /**
     * Parse order item description to extract style, color, and packing way.
     * Format: "BODYROK [STYLE] Sock - [COLOR] - [PACKING WAY]"
     * Example: "BODYROK 1/2 Crew Sock - Black - Hook"
     */
    public static function parseOrderDescription(string $description): array
    {
        // Remove BODYROK prefix if present
        $description = preg_replace('/^BODYROK\s+/i', '', trim($description));
        
        // Split by " - "
        $parts = explode(' - ', $description);
        
        if (count($parts) < 3) {
            return ['style' => '', 'color' => '', 'packing_way' => ''];
        }
        
        $stylePart = trim($parts[0]);
        $color = trim($parts[1]);
        $packingWay = trim($parts[2]);
        
        // Remove "Sock" suffix from style if present
        $style = preg_replace('/\s+Sock$/i', '', $stylePart);
        
        // Normalize packing way (Hook -> hook, Sleeve Wrap -> Sleeve Wrap)
        $packingWay = strtolower($packingWay) === 'hook' ? 'hook' : $packingWay;
        
        return [
            'style' => $style,
            'color' => $color,
            'packing_way' => $packingWay,
        ];
    }

    /**
     * Create order items (allocations) when order is picked.
     */
    public function createOrderItems(): void
    {
        if (empty($this->items) || !is_array($this->items) || !$this->incoming_shipment_id) {
            return;
        }

        $shipment = $this->incomingShipment;
        if (!$shipment) {
            return;
        }

        // Delete existing order items
        $this->orderItems()->delete();

        foreach ($this->items as $orderItem) {
            $description = $orderItem['description'] ?? '';
            $quantityRequired = $orderItem['quantity_required'] ?? 0;
            
            if (empty($description) || $quantityRequired <= 0) {
                continue;
            }

            // Parse description
            $parsed = self::parseOrderDescription($description);
            $style = $parsed['style'];
            $color = $parsed['color'];
            $packingWay = $parsed['packing_way'];

            if (empty($style) || empty($color) || empty($packingWay)) {
                continue;
            }

            // Find matching items in shipment and allocate
            $remainingQty = $quantityRequired;
            $shipmentItems = $shipment->items ?? [];
            
            foreach ($shipmentItems as $index => $shipmentItem) {
                if ($remainingQty <= 0) {
                    break;
                }

                $shipmentStyle = $shipmentItem['style'] ?? '';
                $shipmentColor = $shipmentItem['color'] ?? '';
                $shipmentPackingWay = $shipmentItem['packing_way'] ?? '';
                
                // Normalize for comparison
                $normalizeStyle = function($s) {
                    return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
                };
                
                if ($normalizeStyle($shipmentStyle) === $normalizeStyle($style) &&
                    strtolower(trim($shipmentColor)) === strtolower(trim($color)) &&
                    strtolower(trim($shipmentPackingWay)) === strtolower(trim($packingWay))) {
                    
                    // Check available quantity for this carton
                    $availableQty = $shipment->getAvailableQuantity($shipmentStyle, $shipmentColor, $shipmentPackingWay);
                    $cartonQty = $shipmentItem['quantity'] ?? 0;
                    $allocatableQty = min($remainingQty, $cartonQty, $availableQty);
                    
                    if ($allocatableQty > 0) {
                        \App\Models\OrderItem::create([
                            'order_id' => $this->id,
                            'incoming_shipment_id' => $shipment->id,
                            'shipment_item_index' => $index,
                            'style' => $shipmentStyle,
                            'color' => $shipmentColor,
                            'packing_way' => $shipmentPackingWay,
                            'quantity_required' => $quantityRequired,
                            'quantity_allocated' => $allocatableQty,
                            'warehouse_location' => $orderItem['warehouse_location'] ?? null,
                        ]);
                        
                        $remainingQty -= $allocatableQty;
                    }
                }
            }
        }
    }
}
