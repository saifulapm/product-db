<x-filament-widgets::widget wire:poll.5s>
    <x-filament::section>
        <x-slot name="heading">
            Pick List Receiving
        </x-slot>
        
        <x-slot name="description">
            Match pick list requirements against shipment items. Mark items as picked to fulfill orders.
        </x-slot>
        
        @php
            $receivingData = $this->getReceivingData();
        @endphp
        
        @if(empty($receivingData))
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No pick lists uploaded yet. Upload pick lists to see receiving requirements.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($receivingData as $item)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 {{ $item['can_fulfill'] ? 'bg-green-50 dark:bg-green-900/10 border-green-300 dark:border-green-700' : 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-300 dark:border-yellow-700' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                    {{ $item['style'] }} - {{ $item['color'] }} ({{ $item['packing_way'] }})
                                </h3>
                                <div class="flex items-center gap-4 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <strong>Carton:</strong> {{ $item['carton_number'] ?? 'N/A' }}
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <strong>Available:</strong> <span class="font-semibold text-blue-600 dark:text-blue-400">{{ number_format($item['available_quantity']) }}</span> pcs
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <strong>Needed:</strong> <span class="font-semibold text-orange-600 dark:text-orange-400">{{ number_format($item['total_needed']) }}</span> pcs
                                    </span>
                                    @if($item['total_picked'] > 0)
                                        <span class="text-gray-600 dark:text-gray-400">
                                            <strong>Picked:</strong> <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($item['total_picked']) }}</span> pcs
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="ml-4">
                                @if($item['can_fulfill'])
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        ✓ Can Fulfill
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        ⚠ Insufficient Stock
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Orders Needing This Item:</h4>
                            <div class="space-y-3">
                                @foreach($item['pick_list_requirements'] as $requirement)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900 dark:text-white mb-1">
                                                    {{ $requirement['pick_list_name'] }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    Need: <span class="font-semibold">{{ number_format($requirement['quantity_needed']) }}</span> pcs
                                                    @if($requirement['quantity_picked'] > 0)
                                                        | Picked: <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($requirement['quantity_picked']) }}</span> pcs
                                                    @endif
                                                    | Remaining: <span class="font-semibold text-orange-600 dark:text-orange-400">{{ number_format($requirement['quantity_remaining']) }}</span> pcs
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                @if($requirement['quantity_remaining'] > 0 && $item['available_quantity'] > 0)
                                                    <x-filament::button
                                                        color="success"
                                                        size="xs"
                                                        wire:click="markAsPicked({{ $item['shipment_item_index'] }}, [{'pick_list_index': {{ $requirement['pick_list_index'] }}, 'item_index': {{ $requirement['item_index'] }}, 'quantity': {{ min($requirement['quantity_remaining'], $item['available_quantity']) }}}])"
                                                    >
                                                        Mark {{ number_format(min($requirement['quantity_remaining'], $item['available_quantity'])) }} as Picked
                                                    </x-filament::button>
                                                @elseif($requirement['quantity_remaining'] === 0)
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Fully Picked</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        @if($item['total_needed'] > 0 && $item['available_quantity'] > 0)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                @php
                                    $markAllData = [];
                                    foreach ($item['pick_list_requirements'] as $req) {
                                        $markAllData[] = [
                                            'pick_list_index' => $req['pick_list_index'],
                                            'item_index' => $req['item_index'],
                                            'quantity' => min($req['quantity_remaining'], $item['available_quantity'])
                                        ];
                                    }
                                @endphp
                                <x-filament::button
                                    color="success"
                                    size="sm"
                                    wire:click="markAsPicked({{ $item['shipment_item_index'] }}, @js($markAllData))"
                                >
                                    Mark All Remaining ({{ number_format(min($item['total_needed'], $item['available_quantity'])) }} pcs) as Picked
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

