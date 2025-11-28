<?php

namespace App\Filament\Resources\PackingListResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;

class PickListHistoryWidget extends Widget
{
    protected static string $view = 'filament.resources.packing-list-resource.widgets.pick-list-history-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public array $pickList = [];
    
    public $shipment = null;
    
    public function mount(): void
    {
        // Data will be passed from the view template
    }
    
    public function getHistory(): Collection
    {
        $history = collect();
        $historyEntries = $this->pickList['history'] ?? [];
        
        // If no history entries exist, try to create them from picked_items (backward compatibility)
        if (empty($historyEntries)) {
            $pickedItems = $this->pickList['picked_items'] ?? [];
            $pickListItems = $this->pickList['items'] ?? [];
            
            foreach ($pickedItems as $picked) {
                $itemIndex = $picked['item_index'] ?? null;
                $item = $pickListItems[$itemIndex] ?? null;
                
                if ($item) {
                    // Get item details
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
                    
                    $history->push([
                        'action' => 'picked',
                        'item_description' => trim(($style ?: '') . ' - ' . ($color ?: '') . ' - ' . ($packingWay ?: 'hook')),
                        'quantity' => $picked['quantity_picked'] ?? 0,
                        'carton_number' => $picked['carton_number'] ?? '',
                        'action_at' => $picked['picked_at'] ?? now()->toIso8601String(),
                        'user_id' => $picked['picked_by_user_id'] ?? null,
                        'user_name' => !empty($picked['picked_by_user_name']) ? $picked['picked_by_user_name'] : (!empty($picked['user_name']) ? $picked['user_name'] : 'System'),
                    ]);
                }
            }
        } else {
            // Use history entries
            foreach ($historyEntries as $entry) {
                // Normalize user_name - prioritize user_name, then picked_by_user_name, then default to 'System'
                $userName = 'System';
                if (!empty($entry['user_name'])) {
                    $userName = $entry['user_name'];
                } elseif (!empty($entry['picked_by_user_name'])) {
                    $userName = $entry['picked_by_user_name'];
                }
                
                $history->push([
                    'action' => $entry['action'] ?? 'picked',
                    'item_description' => $entry['item_description'] ?? '',
                    'quantity' => $entry['quantity'] ?? 0,
                    'carton_number' => $entry['carton_number'] ?? '',
                    'action_at' => $entry['action_at'] ?? now()->toIso8601String(),
                    'user_id' => $entry['user_id'] ?? null,
                    'user_name' => $userName,
                ]);
            }
        }
        
        // Sort by action_at descending (most recent first)
        return $history->sortByDesc('action_at')->values();
    }
}

