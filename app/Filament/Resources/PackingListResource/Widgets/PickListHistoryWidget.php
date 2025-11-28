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
        
        // Ensure historyEntries is an array
        if (!is_array($historyEntries)) {
            $historyEntries = [];
        }
        
        // If no history entries exist, try to create them from picked_items (backward compatibility)
        if (empty($historyEntries)) {
            $pickedItems = $this->pickList['picked_items'] ?? [];
            $pickListItems = $this->pickList['items'] ?? [];
            
            // Ensure arrays
            if (!is_array($pickedItems)) {
                $pickedItems = [];
            }
            if (!is_array($pickListItems)) {
                $pickListItems = [];
            }
            
            foreach ($pickedItems as $picked) {
                // Ensure $picked is an array
                if (!is_array($picked)) {
                    continue;
                }
                
                $itemIndex = $picked['item_index'] ?? null;
                $item = isset($pickListItems[$itemIndex]) && is_array($pickListItems[$itemIndex]) ? $pickListItems[$itemIndex] : null;
                
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
                    
                    // Safely get user_name
                    $userName = 'System';
                    if (isset($picked['picked_by_user_name']) && !empty($picked['picked_by_user_name'])) {
                        $userName = (string)$picked['picked_by_user_name'];
                    } elseif (isset($picked['user_name']) && !empty($picked['user_name'])) {
                        $userName = (string)$picked['user_name'];
                    }
                    
                    $history->push([
                        'action' => 'picked',
                        'item_description' => trim(($style ?: '') . ' - ' . ($color ?: '') . ' - ' . ($packingWay ?: 'hook')),
                        'quantity' => isset($picked['quantity_picked']) ? (int)($picked['quantity_picked']) : 0,
                        'carton_number' => isset($picked['carton_number']) ? (string)$picked['carton_number'] : '',
                        'action_at' => isset($picked['picked_at']) ? (string)$picked['picked_at'] : now()->toIso8601String(),
                        'user_id' => isset($picked['picked_by_user_id']) ? $picked['picked_by_user_id'] : null,
                        'user_name' => $userName,
                    ]);
                }
            }
        } else {
            // Use history entries
            foreach ($historyEntries as $entry) {
                // Ensure $entry is an array
                if (!is_array($entry)) {
                    continue;
                }
                
                // Normalize user_name - prioritize user_name, then picked_by_user_name, then default to 'System'
                $userName = 'System';
                if (isset($entry['user_name']) && !empty($entry['user_name'])) {
                    $userName = (string)$entry['user_name'];
                } elseif (isset($entry['picked_by_user_name']) && !empty($entry['picked_by_user_name'])) {
                    $userName = (string)$entry['picked_by_user_name'];
                }
                
                $history->push([
                    'action' => isset($entry['action']) ? (string)$entry['action'] : 'picked',
                    'item_description' => isset($entry['item_description']) ? (string)$entry['item_description'] : '',
                    'quantity' => isset($entry['quantity']) ? (int)$entry['quantity'] : 0,
                    'carton_number' => isset($entry['carton_number']) ? (string)$entry['carton_number'] : '',
                    'action_at' => isset($entry['action_at']) ? (string)$entry['action_at'] : now()->toIso8601String(),
                    'user_id' => isset($entry['user_id']) ? $entry['user_id'] : null,
                    'user_name' => $userName,
                ]);
            }
        }
        
        // Ensure all entries have user_name set and remove any picked_by_user_name references
        $history = $history->map(function ($entry) {
            if (!is_array($entry)) {
                return $entry;
            }
            
            // Normalize user_name - ensure it's always set and remove picked_by_user_name
            $userName = 'System';
            if (isset($entry['user_name']) && !empty($entry['user_name'])) {
                $userName = (string)$entry['user_name'];
            } elseif (isset($entry['picked_by_user_name']) && !empty($entry['picked_by_user_name'])) {
                $userName = (string)$entry['picked_by_user_name'];
            }
            
            // Create a clean entry array with only the fields we need
            $cleanEntry = [
                'action' => isset($entry['action']) ? (string)$entry['action'] : 'picked',
                'item_description' => isset($entry['item_description']) ? (string)$entry['item_description'] : '',
                'quantity' => isset($entry['quantity']) ? (int)$entry['quantity'] : 0,
                'carton_number' => isset($entry['carton_number']) ? (string)$entry['carton_number'] : '',
                'action_at' => isset($entry['action_at']) ? (string)$entry['action_at'] : now()->toIso8601String(),
                'user_id' => isset($entry['user_id']) ? $entry['user_id'] : null,
                'user_name' => $userName, // Always set, never picked_by_user_name
            ];
            
            return $cleanEntry;
        });
        
        // Sort by action_at descending (most recent first)
        return $history->sortByDesc('action_at')->values();
    }
}

