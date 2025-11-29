<?php

namespace App\Filament\Resources\IncomingShipmentResource\Widgets;

use App\Models\IncomingShipment;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;
use Carbon\Carbon;

class ShipmentTimelineWidget extends Widget
{
    protected static string $view = 'filament.resources.incoming-shipment-resource.widgets.shipment-timeline-widget';

    protected int | string | array $columnSpan = 'full';

    #[Reactive]
    public ?IncomingShipment $record = null;

    public function getTimelineEvents(): array
    {
        if (!$this->record) {
            return [];
        }

        $events = [];

        // Event 1: Shipment Created
        if ($this->record->created_at) {
            $events[] = [
                'type' => 'created',
                'title' => 'Shipment Created',
                'description' => 'Shipment ' . ($this->record->name ?? 'N/A') . ' was created',
                'timestamp' => $this->record->created_at,
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'primary',
            ];
        }

        // Event 2: Tracking Added
        if ($this->record->tracking_added_at) {
            $events[] = [
                'type' => 'tracking_added',
                'title' => 'Tracking Information Added',
                'description' => 'Tracking number ' . ($this->record->tracking_number ?? 'N/A') . ' was added',
                'timestamp' => $this->record->tracking_added_at,
                'icon' => 'heroicon-o-truck',
                'color' => 'info',
            ];
        }

        // Event 3: Products Received
        $receiveHistory = $this->record->receive_history ?? [];
        if (!empty($receiveHistory)) {
            // Group by timestamp to show multiple receives at once
            $receivesByDate = [];
            foreach ($receiveHistory as $receive) {
                $date = $receive['received_at'] ?? now()->toDateTimeString();
                if (!isset($receivesByDate[$date])) {
                    $receivesByDate[$date] = [];
                }
                $receivesByDate[$date][] = $receive;
            }

            foreach ($receivesByDate as $date => $receives) {
                $totalReceived = 0;
                $totalQuantity = 0;
                $items = [];
                
                foreach ($receives as $receive) {
                    $receivedQty = (int)($receive['received_qty'] ?? 0);
                    $quantity = (int)($receive['quantity'] ?? 0);
                    $totalReceived += $receivedQty;
                    $totalQuantity += $quantity;
                    
                    $productName = $receive['product_name'] ?? '';
                    // If product_name is empty, try to construct from style and color
                    if (empty($productName)) {
                        $style = $receive['style'] ?? '';
                        $color = $receive['color'] ?? '';
                        $productName = !empty($style) && !empty($color) 
                            ? $style . ' - ' . $color 
                            : ($style ?: $color ?: 'Unknown Product');
                    }
                    
                    $items[] = [
                        'carton' => $receive['carton_number'] ?? '',
                        'product' => $productName,
                        'received' => $receivedQty,
                        'quantity' => $quantity,
                    ];
                }

                $isPartial = $totalReceived < $totalQuantity;
                $isFull = $totalReceived >= $totalQuantity;

                $events[] = [
                    'type' => 'received',
                    'title' => $isPartial ? 'Partial Products Received' : 'Products Received',
                    'description' => $isPartial 
                        ? "Received {$totalReceived} of {$totalQuantity} items"
                        : "Received {$totalReceived} items (full quantity)",
                    'timestamp' => Carbon::parse($date),
                    'icon' => $isPartial ? 'heroicon-o-inbox-arrow-down' : 'heroicon-o-check-circle',
                    'color' => $isPartial ? 'warning' : 'success',
                    'items' => $items,
                ];
            }
        }

        // Sort events by timestamp (newest first)
        usort($events, function ($a, $b) {
            return $b['timestamp']->timestamp <=> $a['timestamp']->timestamp;
        });

        return $events;
    }
}

