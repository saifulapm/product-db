<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'incoming_shipment_id',
        'shipment_item_index',
        'style',
        'color',
        'packing_way',
        'quantity_required',
        'quantity_allocated',
        'warehouse_location',
    ];

    /**
     * Get the order this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the incoming shipment this item is allocated from.
     */
    public function incomingShipment(): BelongsTo
    {
        return $this->belongsTo(IncomingShipment::class);
    }
}
