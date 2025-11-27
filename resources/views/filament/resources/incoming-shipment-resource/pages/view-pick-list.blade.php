<x-filament-panels::page>
    @php
        $pickListItems = $this->pickList['items'] ?? [];
        $pickedItems = $this->pickList['picked_items'] ?? [];
        
        // Create a map of picked quantities by item index
        $pickedByItemIndex = [];
        foreach ($pickedItems as $picked) {
            $idx = $picked['item_index'] ?? null;
            if ($idx !== null) {
                if (!isset($pickedByItemIndex[$idx])) {
                    $pickedByItemIndex[$idx] = 0;
                }
                $pickedByItemIndex[$idx] += $picked['quantity_picked'] ?? 0;
            }
        }
        
        $availableQuantities = $this->shipment ? $this->shipment->getAvailableQuantitiesByCarton([$this->pickList]) : [];
    @endphp
    
    <div x-data="{ selectedItems: {} }" class="space-y-6">
        <!-- Bulk Actions Bar -->
        <div 
            x-show="Object.keys(selectedItems).filter(k => selectedItems[k] === true).length > 0"
            x-transition
            class="mb-4 px-6 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span x-text="Object.keys(selectedItems).filter(k => selectedItems[k] === true).length"></span> item(s) selected
                </span>
                <div class="flex gap-2">
                    <x-filament::button
                        color="success"
                        size="sm"
                        x-on:click="
                            const selected = Object.keys(selectedItems).filter(k => selectedItems[k] === true);
                            if (selected.length > 0) {
                                const itemData = selected.map(k => {
                                    const parts = k.split('_');
                                    return {
                                        itemIndex: parseInt(parts[0]),
                                        shipmentItemIndex: parseInt(parts[1]),
                                        quantity: parseInt(parts[2])
                                    };
                                });
                                @this.call('bulkMarkItemsAsPicked', itemData);
                            }
                        "
                    >
                        Mark Selected as Picked
                    </x-filament::button>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        x-on:click="
                            const selected = Object.keys(selectedItems).filter(k => selectedItems[k] === true);
                            if (selected.length > 0 && confirm('Delete ' + selected.length + ' selected item(s)?')) {
                                const itemIndices = selected.map(k => parseInt(k.split('_')[0])).sort((a, b) => b - a);
                                @this.call('bulkDeletePickListItems', itemIndices);
                            }
                        "
                    >
                        Delete Selected
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="selectedItems = {}"
                    >
                        Clear Selection
                    </x-filament::button>
                </div>
            </div>
        </div>
        
        @if(empty($pickListItems))
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No items in this pick list.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <input 
                                    type="checkbox"
                                    x-on:change="
                                        const allChecked = $event.target.checked;
                                        @foreach($pickListItems as $itemIndex => $orderItem)
                                            @php
                                                if (isset($orderItem['description'])) {
                                                    $parsed = \App\Models\Order::parseOrderDescription($orderItem['description']);
                                                    $style = $parsed['style'] ?? '';
                                                    $color = $parsed['color'] ?? '';
                                                    $packingWay = $parsed['packing_way'] ?? 'hook';
                                                    $quantityNeeded = $orderItem['quantity_required'] ?? $orderItem['quantity'] ?? 0;
                                                } else {
                                                    $style = $orderItem['style'] ?? '';
                                                    $color = $orderItem['color'] ?? '';
                                                    $packingWay = $orderItem['packing_way'] ?? 'hook';
                                                    $quantityNeeded = $orderItem['quantity'] ?? 0;
                                                }
                                                
                                                $quantityPicked = $pickedByItemIndex[$itemIndex] ?? 0;
                                                $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
                                                
                                                // Find matching shipment item
                                                $matchingShipmentIndex = null;
                                                if (!empty($this->shipment->items) && is_array($this->shipment->items)) {
                                                    $normalizedStyle = strtolower(trim($style));
                                                    $normalizedColor = trim(strtolower(trim($color)), ' -');
                                                    $normalizedPackingWay = strtolower(trim($packingWay));
                                                    
                                                    if (strpos($normalizedPackingWay, 'hook') !== false) {
                                                        $normalizedPackingWay = 'hook';
                                                    } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                                                        $normalizedPackingWay = 'sleeve wrap';
                                                    }
                                                    
                                                    foreach ($this->shipment->items as $shipIndex => $shipItem) {
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
                                                            $matchingShipmentIndex = $shipIndex;
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                $itemKey = $itemIndex . '_' . ($matchingShipmentIndex ?? 'null') . '_' . $quantityRemaining;
                                            @endphp
                                            selectedItems['{{ $itemKey }}'] = allChecked;
                                        @endforeach
                                    "
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Style</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Color</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Packing Way</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qty Needed</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Picked</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Remaining</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pick From Carton(s)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pickListItems as $itemIndex => $orderItem)
                            @php
                                if (isset($orderItem['description'])) {
                                    $parsed = \App\Models\Order::parseOrderDescription($orderItem['description']);
                                    $style = $parsed['style'] ?? '';
                                    $color = $parsed['color'] ?? '';
                                    $packingWay = $parsed['packing_way'] ?? 'hook';
                                    $quantityNeeded = $orderItem['quantity_required'] ?? $orderItem['quantity'] ?? 0;
                                } else {
                                    $style = $orderItem['style'] ?? '';
                                    $color = $orderItem['color'] ?? '';
                                    $packingWay = $orderItem['packing_way'] ?? 'hook';
                                    $quantityNeeded = $orderItem['quantity'] ?? 0;
                                }
                                
                                $quantityPicked = $pickedByItemIndex[$itemIndex] ?? 0;
                                $quantityRemaining = max(0, $quantityNeeded - $quantityPicked);
                                
                                // Find matching shipment item index and cartons
                                $matchingShipmentIndex = null;
                                $availableCartons = $this->shipment ? $this->shipment->getAvailableCartonsForItem($style, $color, $packingWay, $quantityRemaining, [$this->pickList]) : [];
                                
                                if (!empty($this->shipment->items) && is_array($this->shipment->items)) {
                                    $normalizedStyle = strtolower(trim($style));
                                    $normalizedColor = trim(strtolower(trim($color)), ' -');
                                    $normalizedPackingWay = strtolower(trim($packingWay));
                                    
                                    if (strpos($normalizedPackingWay, 'hook') !== false) {
                                        $normalizedPackingWay = 'hook';
                                    } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                                        $normalizedPackingWay = 'sleeve wrap';
                                    }
                                    
                                    foreach ($this->shipment->items as $shipIndex => $shipItem) {
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
                                            $matchingShipmentIndex = $shipIndex;
                                            break;
                                        }
                                    }
                                }
                                
                                $pickGuidance = '';
                                $rowClass = '';
                                
                                if ($quantityRemaining === 0) {
                                    $rowClass = 'bg-blue-50/50 dark:bg-blue-900/10';
                                    $pickGuidance = '<span class="text-blue-600 dark:text-blue-400 font-semibold">✓ Fully Picked</span>';
                                } elseif (!empty($availableCartons['cartons'])) {
                                    $firstCarton = $availableCartons['cartons'][0];
                                    $pickGuidance = '<span class="text-green-600 dark:text-green-400 font-semibold">✓ Pick from: <strong>CTN#' . $firstCarton['carton_number'] . '</strong> (' . number_format($firstCarton['available_quantity']) . ' pcs available)';
                                    
                                    if ($quantityRemaining > $firstCarton['available_quantity'] && count($availableCartons['cartons']) > 1) {
                                        $additional = collect(array_slice($availableCartons['cartons'], 1))
                                            ->map(fn($c) => 'CTN#' . $c['carton_number'] . ' (' . number_format($c['available_quantity']) . ' pcs)')
                                            ->implode(', ');
                                        $pickGuidance .= ' • Also check: ' . $additional;
                                    }
                                    $pickGuidance .= '</span>';
                                } else {
                                    $pickGuidance = '<span class="text-red-600 dark:text-red-400 font-semibold">✗ No matching cartons found</span>';
                                }
                                
                                $itemKey = $itemIndex . '_' . ($matchingShipmentIndex ?? 'null') . '_' . $quantityRemaining;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $rowClass }}">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <input 
                                        type="checkbox"
                                        x-model="selectedItems['{{ $itemKey }}']"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $style ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $color ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $packingWay ?: 'hook' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-semibold">
                                    {{ number_format($quantityNeeded) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $quantityPicked > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ number_format($quantityPicked) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $quantityRemaining > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ number_format($quantityRemaining) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {!! $pickGuidance !!}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($quantityRemaining > 0 && $matchingShipmentIndex !== null)
                                        <x-filament::button
                                            color="success"
                                            size="xs"
                                            wire:click="markItemAsPicked({{ $itemIndex }}, {{ $matchingShipmentIndex }}, {{ $quantityRemaining }})"
                                        >
                                            Mark as Picked
                                        </x-filament::button>
                                    @elseif($quantityRemaining === 0)
                                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Picked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">Totals:</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                {{ number_format(collect($pickListItems)->sum(fn($item) => $item['quantity'] ?? $item['quantity_required'] ?? 0)) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                {{ number_format(array_sum($pickedByItemIndex)) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-orange-600 dark:text-orange-400">
                                {{ number_format(collect($pickListItems)->sum(function($item, $idx) use ($pickedByItemIndex) {
                                    $qty = $item['quantity'] ?? $item['quantity_required'] ?? 0;
                                    $picked = $pickedByItemIndex[$idx] ?? 0;
                                    return max(0, $qty - $picked);
                                })) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>

