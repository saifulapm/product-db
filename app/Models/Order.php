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
        
        // Remove any trailing quantity that might have been merged (e.g., "Hook 50" -> "Hook")
        $description = preg_replace('/\s+\d+\s*(?:pcs|pieces|qty|quantity)?\s*$/i', '', $description);
        
        // Try splitting by " - " first
        $parts = explode(' - ', $description);
        
        if (count($parts) >= 3) {
            // Standard format: "Style Sock - Color - Packing Way"
            $stylePart = trim($parts[0]);
            $color = trim($parts[1]);
            $packingWayRaw = trim($parts[2]);
            
            // Remove any trailing numbers from packing way (e.g., "Hook 50" -> "Hook")
            $packingWay = preg_replace('/\s+\d+\s*$/i', '', $packingWayRaw);
            $packingWay = trim($packingWay);
        } elseif (count($parts) === 2) {
            // Format might be: "Style Sock - Color Hook" or "Style Sock - Color Hook 50"
            $stylePart = trim($parts[0]);
            $colorAndPacking = trim($parts[1]);
            
            // Remove trailing quantity first
            $colorAndPacking = preg_replace('/\s+\d+\s*(?:pcs|pieces|qty|quantity)?\s*$/i', '', $colorAndPacking);
            
            // Try to extract packing way from the end (Hook, Sleeve Wrap)
            if (preg_match('/\s+(Hook|Sleeve\s*Wrap)$/i', $colorAndPacking, $matches)) {
                $packingWay = strtolower(trim($matches[1])) === 'hook' ? 'hook' : trim($matches[1]);
                $color = trim(preg_replace('/\s+(Hook|Sleeve\s*Wrap)$/i', '', $colorAndPacking));
            } else {
                // Fallback: assume hook if not found
                $color = $colorAndPacking;
                $packingWay = 'hook';
            }
        } else {
            // Single part - try to extract what we can
            $stylePart = trim($description);
            $color = '';
            $packingWay = 'hook';
            
            // Remove trailing quantity
            $stylePart = preg_replace('/\s+\d+\s*(?:pcs|pieces|qty|quantity)?\s*$/i', '', $stylePart);
            
            // Try to find packing way at the end
            if (preg_match('/\s+(Hook|Sleeve\s*Wrap)$/i', $stylePart, $matches)) {
                $packingWay = strtolower(trim($matches[1])) === 'hook' ? 'hook' : trim($matches[1]);
                $stylePart = trim(preg_replace('/\s+(Hook|Sleeve\s*Wrap)$/i', '', $stylePart));
            }
        }
        
        // Remove "Sock" suffix from style if present
        $style = preg_replace('/\s+Sock$/i', '', $stylePart);
        
        // Normalize packing way (Hook -> hook, Sleeve Wrap -> Sleeve Wrap)
        $packingWay = strtolower($packingWay) === 'hook' ? 'hook' : trim($packingWay);
        
        // Clean up color - remove trailing dashes or spaces
        $color = trim($color, ' -');
        
        return [
            'style' => trim($style),
            'color' => trim($color),
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
