<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Shipments</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                View all committed shipments and their details.
            </p>
        </div>
        
        {{-- Search Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by order number or supply name..."
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                    />
                </div>
            </div>
        </div>

        @php
            $shipmentsData = $this->getShipmentsData();
        @endphp

        @if(empty($shipmentsData))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                    @if(!empty($this->search))
                        No shipments found matching your search. Try a different search term.
                    @else
                        No shipments found. Commit shipments from the "Create A Shipment" page to see them here.
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                @php
                                    $columns = [
                                        'order_number' => 'Order Number',
                                        'supply_name' => 'Supply Name',
                                        'supply_type' => 'Type',
                                        'garment_quantity' => 'Garment Quantity',
                                        'used_at' => 'Committed At',
                                    ];
                                @endphp

                                @foreach($columns as $columnName => $columnLabel)
                                    <th 
                                        wire:click="sortBy('{{ $columnName }}')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>{{ $columnLabel }}</span>
                                            @if($this->sortColumn === $columnName)
                                                @if($this->sortDirection === 'asc')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($shipmentsData as $shipment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $shipment['order_number'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $shipment['supply_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($shipment['supply_type'] === 'box') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($shipment['supply_type'] === 'mailer') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @endif">
                                            {{ ucfirst($shipment['supply_type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $shipment['garment_quantity'] }} {{ $shipment['garment_quantity'] == 1 ? 'piece' : 'pieces' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if(!empty($shipment['used_at']))
                                            {{ \Carbon\Carbon::parse($shipment['used_at'])->format('M d, Y g:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

