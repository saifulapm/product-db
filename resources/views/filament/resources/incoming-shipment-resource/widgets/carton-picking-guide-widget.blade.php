<x-filament-widgets::widget wire:poll.5s>
    <x-filament::section>
        <x-slot name="heading">
            Carton-Based Picking Guide
        </x-slot>
        
        <x-slot name="description">
            See which orders need items from each carton. This view helps you pick multiple orders efficiently by carton.
        </x-slot>
        
        @php
            $cartonData = $this->getCartonPickingData();
        @endphp
        
        @if(empty($cartonData))
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">No pick lists uploaded yet. Upload pick lists to see carton-based picking guidance.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($cartonData as $carton)
                    @php
                        $hasOrdersNeeding = !empty($carton['orders_needing']);
                    @endphp
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 {{ $hasOrdersNeeding ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-300 dark:border-blue-700' : 'bg-gray-50 dark:bg-gray-800' }}">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                Carton #{{ $carton['carton_number'] }}
                            </h3>
                            @if($hasOrdersNeeding)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ count($carton['orders_needing']) }} order(s) need items
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    No orders need items
                                </span>
                            @endif
                        </div>
                        
                        @if(!empty($carton['items']))
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contents:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                    @foreach($carton['items'] as $item)
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">{{ $item['style'] }}</span> - 
                                            {{ $item['color'] }} ({{ $item['packing_way'] }}) - 
                                            <span class="font-semibold">{{ number_format($item['quantity']) }} pcs</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if($hasOrdersNeeding)
                            <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Orders Needing Items from This Carton:</h4>
                                <div class="space-y-4">
                                    @foreach($carton['orders_needing'] as $orderInfo)
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                                            <h5 class="font-semibold text-gray-900 dark:text-white mb-2">
                                                {{ $orderInfo['pick_list_name'] }}
                                            </h5>
                                            <div class="space-y-2">
                                                @foreach($orderInfo['items'] as $item)
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="text-gray-700 dark:text-gray-300">
                                                            <span class="font-medium">{{ $item['style'] }}</span> - 
                                                            {{ $item['color'] }} ({{ $item['packing_way'] }})
                                                        </span>
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-gray-600 dark:text-gray-400">
                                                                Need: <span class="font-semibold">{{ number_format($item['quantity_remaining']) }}</span>
                                                            </span>
                                                            <span class="text-green-600 dark:text-green-400">
                                                                Available: <span class="font-semibold">{{ number_format($item['available_in_carton']) }}</span>
                                                            </span>
                                                            @if($item['quantity_remaining'] > $item['available_in_carton'])
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                                    Need more cartons
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

