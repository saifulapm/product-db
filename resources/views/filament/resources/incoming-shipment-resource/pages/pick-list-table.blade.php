@php
    $pickListIndex = $pickListIndex ?? 0;
    $pickList = $pickList ?? [];
    $pickListItems = $pickList['items'] ?? [];
    $pickListId = $pickList['id'] ?? uniqid('pl_', true);
    $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
    $record = $record ?? null;
    $selectedItems = $selectedItems ?? [];
    $fileName = $pickList['filename'] ?? null;
    $uploadedAt = $pickList['uploaded_at'] ?? '';
@endphp

<div 
    x-data="{ 
        selectedItems: @js($selectedItems),
        init() {
            // Sync selections when Livewire updates
            this.$watch('$wire.selectedPickListItems', (value) => {
                if (value) {
                    // Update Alpine.js selectedItems from Livewire state
                    const updated = {};
                    Object.keys(value).forEach(key => {
                        if (key.startsWith('{{ $pickListIndex }}_')) {
                            updated[key] = value[key];
                        }
                    });
                    this.selectedItems = updated;
                } else {
                    this.selectedItems = {};
                }
            });
            
            // Listen for pick-list-updated event to clear selections
            window.addEventListener('pick-list-updated', () => {
                setTimeout(() => {
                    this.selectedItems = {};
                }, 100);
            });
        }
    }" 
    style="width: 100%; margin: 0; padding: 0; overflow-x: auto;"
>
    <!-- Uploaded File Info -->
    @if($fileName)
        <div class="mb-4 px-6 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            Uploaded File: <span class="text-blue-600 dark:text-blue-400">{{ $fileName }}</span>
                        </div>
                        @if($uploadedAt)
                            @php
                                try {
                                    $uploadedAtFormatted = \Carbon\Carbon::parse($uploadedAt)->format('M d, Y g:i A');
                                } catch (\Exception $e) {
                                    $uploadedAtFormatted = $uploadedAt;
                                }
                            @endphp
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Uploaded: {{ $uploadedAtFormatted }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Bulk Actions Bar -->
    <div 
        id="bulk-actions-{{ $pickListIndex }}" 
        class="mb-4 px-6 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" 
        x-show="Object.keys(selectedItems || {}).filter(k => selectedItems[k] === true).length > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
    >
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <span x-text="Object.keys(selectedItems).filter(k => selectedItems[k]).length"></span> item(s) selected
            </span>
            <div class="flex gap-2">
                <button 
                    type="button"
                    x-on:click="
                        const selected = Object.keys(selectedItems).filter(k => selectedItems[k]);
                        if (selected.length === 0) {
                            alert('Please select at least one item.');
                            return;
                        }
                        if (confirm('Mark ' + selected.length + ' selected item(s) as picked? This will mark the remaining quantity for each item.')) {
                            const itemData = selected.map(k => {
                                const parts = k.split('_');
                                return { pickListIndex: {{ $pickListIndex }}, itemIndex: parseInt(parts[1]) };
                            });
                            @this.call('bulkMarkItemsAsPicked', {{ $pickListIndex }}, itemData);
                        }
                    "
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded hover:bg-primary-700 transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Mark Selected as Picked
                </button>
                <button 
                    type="button"
                    x-on:click="
                        const selected = Object.keys(selectedItems || {}).filter(k => selectedItems[k] === true);
                        if (selected.length === 0) {
                            alert('Please select at least one item.');
                            return;
                        }
                        if (confirm('Delete ' + selected.length + ' selected item(s) from this pick list? This action cannot be undone.')) {
                            const itemIndices = selected.map(k => parseInt(k.split('_')[1])).sort((a, b) => b - a);
                            @this.call('bulkDeletePickListItems', {{ $pickListIndex }}, itemIndices).then(() => {
                                // Clear selections after successful action
                                selected.forEach(k => selectedItems[k] = false);
                            });
                        }
                    "
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Selected
                </button>
                <button 
                    type="button"
                    x-on:click="selectedItems = {}"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    Clear Selection
                </button>
            </div>
        </div>
    </div>
    
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 border-x-0 border-y border-gray-200 dark:border-gray-700" style="width: 100%; table-layout: fixed; margin: 0;">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th style="width: 4%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                    <input 
                        type="checkbox" 
                        x-on:change="
                            const allChecked = $event.target.checked;
                            @foreach($pickListItems as $itemIndex => $item)
                                selectedItems['{{ $pickListIndex }}_{{ $itemIndex }}'] = allChecked;
                            @endforeach
                        "
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                        style="width: 1rem; height: 1rem;"
                    />
                </th>
                <th style="width: 4%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">#</th>
                <th style="width: 14%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Style</th>
                <th style="width: 14%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Color</th>
                <th style="width: 9%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Packing Way</th>
                <th style="width: 7%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Qty Needed</th>
                <th style="width: 7%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Picked</th>
                <th style="width: 7%;" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">Remaining</th>
                <th style="width: 34%;" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pick From Carton(s)</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @php
                $pickedItems = $pickList['picked_items'] ?? [];
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
                $availableQuantities = $record ? $record->getAvailableQuantitiesByCarton([$pickList]) : [];
            @endphp
            
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
                    
                    $matchingShipmentIndex = null;
                    if (!empty($record->items) && is_array($record->items)) {
                        $normalizedStyle = strtolower(trim($style));
                        $normalizedColor = trim(strtolower(trim($color)), ' -');
                        $normalizedPackingWay = strtolower(trim($packingWay));
                        
                        if (strpos($normalizedPackingWay, 'hook') !== false) {
                            $normalizedPackingWay = 'hook';
                        } elseif (strpos($normalizedPackingWay, 'sleeve') !== false || strpos($normalizedPackingWay, 'wrap') !== false) {
                            $normalizedPackingWay = 'sleeve wrap';
                        }
                        
                        foreach ($record->items as $shipIndex => $shipItem) {
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
                    
                    $availableCartons = $record ? $record->getAvailableCartonsForItem($style, $color, $packingWay, $quantityRemaining, [$pickList]) : ['cartons' => [], 'total_available' => 0, 'can_fulfill' => false];
                    
                    $pickGuidance = '';
                    $rowClass = '';
                    
                    if ($quantityRemaining === 0) {
                        $rowClass = 'bg-blue-50/50 dark:bg-blue-900/10';
                        $pickGuidance = '<span class="text-blue-600 dark:text-blue-400 font-semibold">✓ Fully Picked</span>';
                    } elseif ($availableCartons['can_fulfill']) {
                        $cartonDetails = collect($availableCartons['cartons'])->map(function ($carton) {
                            return '<strong>CTN#' . $carton['carton_number'] . '</strong> (' . $carton['available_quantity'] . ' pcs)';
                        })->implode(', ');
                        $pickGuidance = '<span class="text-green-600 dark:text-green-400 font-semibold">✓ Pick from: ' . $cartonDetails . '</span>';
                        $rowClass = 'bg-green-50/50 dark:bg-green-900/10';
                    } else {
                        $cartonDetails = collect($availableCartons['cartons'])->map(function ($carton) {
                            return 'CTN#' . $carton['carton_number'] . ' (' . $carton['available_quantity'] . ' pcs)';
                        })->implode(', ');
                        $pickGuidance = '<span class="text-red-600 dark:text-red-400 font-semibold">✗ Not enough stock. Need: ' . number_format($quantityRemaining) . ', Available: ' . number_format($availableCartons['total_available']) . '. Check: ' . ($cartonDetails ?: 'No matching cartons') . '</span>';
                        $rowClass = 'bg-red-50/50 dark:bg-red-900/10';
                    }
                    
                    $itemKey = $pickListIndex . '_' . $itemIndex;
                    $isSelected = isset($selectedItems[$itemKey]);
                @endphp
                
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $rowClass }} {{ $isSelected ? 'bg-primary-50/30 dark:bg-primary-900/10' : '' }}" 
                    x-bind:class="{ 'bg-primary-50/30 dark:bg-primary-900/10': selectedItems['{{ $itemKey }}'] }"
                >
                    <td class="px-4 py-3 whitespace-nowrap border-r border-gray-200 dark:border-gray-700">
                        <input 
                            type="checkbox" 
                            class="item-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                            x-model="selectedItems['{{ $itemKey }}']"
                            style="width: 1rem; height: 1rem;"
                        />
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 font-medium">{{ $itemIndex + 1 }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 font-medium">{{ $style ?: '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">{{ $color ?: '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">{{ $packingWay ?: 'hook' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-semibold border-r border-gray-200 dark:border-gray-700">{{ number_format($quantityNeeded) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium border-r border-gray-200 dark:border-gray-700 {{ $quantityPicked > 0 ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($quantityPicked) }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium border-r border-gray-200 dark:border-gray-700 {{ $quantityRemaining > 0 ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($quantityRemaining) }}</td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span>{!! $pickGuidance !!}</span>
                            @if($quantityRemaining > 0 && $matchingShipmentIndex !== null)
                                <button 
                                    type="button"
                                    wire:click="markItemAsPicked({{ $pickListIndex }}, {{ $itemIndex }}, {{ $matchingShipmentIndex }}, {{ $quantityRemaining }})"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-primary-600 rounded hover:bg-primary-700 transition-colors"
                                >
                                    Mark as Picked
                                </button>
                            @elseif($quantityRemaining === 0)
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">✓ Picked</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-gray-900 font-semibold">
            @php
                $totalQty = array_sum(array_column($pickListItems, 'quantity'));
                $totalPicked = array_sum($pickedByItemIndex);
                $totalRemaining = $totalQty - $totalPicked;
                $fulfillableCount = 0;
                foreach ($pickListItems as $itemIndex => $orderItem) {
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
                    $qtyPicked = $pickedByItemIndex[$itemIndex] ?? 0;
                    $qtyRemaining = max(0, $quantityNeeded - $qtyPicked);
                    if ($qtyRemaining > 0 && $record) {
                        $cartons = $record->getAvailableCartonsForItem($style, $color, $packingWay, $qtyRemaining, [$pickList]);
                        if ($cartons['can_fulfill']) {
                            $fulfillableCount++;
                        }
                    } elseif ($qtyRemaining === 0) {
                        $fulfillableCount++;
                    }
                }
            @endphp
            <tr>
                <td colspan="5" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">Totals:</td>
                <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">{{ number_format($totalQty) }}</td>
                <td class="px-4 py-3 text-right text-sm text-blue-600 dark:text-blue-400 border-r border-gray-200 dark:border-gray-700">{{ number_format($totalPicked) }}</td>
                <td class="px-4 py-3 text-right text-sm text-orange-600 dark:text-orange-400 border-r border-gray-200 dark:border-gray-700">{{ number_format($totalRemaining) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                    <span class="{{ $fulfillableCount === count($pickListItems) ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                        {{ $fulfillableCount }} of {{ count($pickListItems) }} items fulfillable
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

