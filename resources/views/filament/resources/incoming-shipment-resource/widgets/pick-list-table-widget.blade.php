<x-filament-widgets::widget wire:poll.5s>
    <x-filament::section>
        <x-slot name="heading">
            Pick List Items
        </x-slot>
        
        @php
            $items = $this->getTableData();
            $groupedItems = collect($items)->groupBy('pick_list_name');
        @endphp
        
        @if(empty($items))
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No pick list items found. Upload pick list files to see items here.</p>
            </div>
        @else
            @foreach($groupedItems as $pickListName => $pickListItems)
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $pickListName }}</h3>
                    
                    <div x-data="{ selectedItems: @js($selectedItems) }">
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
                                                @this.call('bulkMarkAsPicked', selected);
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
                                                @this.call('bulkDeleteItems', selected);
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
                        
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            <input 
                                                type="checkbox"
                                                x-on:change="
                                                    const allChecked = $event.target.checked;
                                                    @foreach($pickListItems as $item)
                                                        selectedItems['{{ $item['id'] }}'] = allChecked;
                                                    @endforeach
                                                    @this.set('selectedItems', selectedItems);
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
                                    @foreach($pickListItems as $item)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input 
                                                    type="checkbox"
                                                    x-model="selectedItems['{{ $item['id'] }}']"
                                                    x-on:change="@this.set('selectedItems', selectedItems)"
                                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $item['style'] ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $item['color'] ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                    {{ $item['packing_way'] ?: 'hook' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right font-semibold">
                                                {{ number_format($item['quantity_needed']) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $item['quantity_picked'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ number_format($item['quantity_picked']) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium {{ $item['quantity_remaining'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ number_format($item['quantity_remaining']) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                {!! $item['carton_guidance'] !!}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($item['quantity_remaining'] > 0 && $item['shipment_item_index'] !== null)
                                                    <x-filament::button
                                                        color="success"
                                                        size="xs"
                                                        wire:click="markItemAsPicked('{{ $item['id'] }}')"
                                                    >
                                                        Mark as Picked
                                                    </x-filament::button>
                                                @elseif($item['quantity_remaining'] === 0)
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Picked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

