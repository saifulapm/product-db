<x-filament-widgets::widget>
    @php
        try {
            $history = $this->getHistory();
        } catch (\Exception $e) {
            \Log::error('PickListHistoryWidget Error: ' . $e->getMessage());
            $history = collect([]);
        }
    @endphp
    
    <x-filament::section>
        <x-slot name="heading">
            Pick List History
        </x-slot>
        
        @if($history->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No picking history available.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Carton</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($history as $entry)
                            @php
                                // Ultra-defensive: ensure entry is array and has all required keys
                                if (!is_array($entry)) {
                                    continue;
                                }
                                
                                try {
                                    $actionAt = \Carbon\Carbon::parse($entry['action_at'] ?? $entry['picked_at'] ?? now());
                                    $formattedDate = $actionAt->format('M d, Y');
                                    $formattedTime = $actionAt->format('g:i A');
                                } catch (\Exception $e) {
                                    $formattedDate = 'Unknown';
                                    $formattedTime = '';
                                }
                                
                                $action = isset($entry['action']) ? (string)$entry['action'] : 'picked';
                                $isPicked = $action === 'picked';
                                $itemDescription = isset($entry['item_description']) ? (string)$entry['item_description'] : 'N/A';
                                $quantity = isset($entry['quantity']) ? (int)$entry['quantity'] : 0;
                                $cartonNumber = isset($entry['carton_number']) ? (string)$entry['carton_number'] : '';
                                
                                // Safely get user_name - NEVER access picked_by_user_name directly
                                $userName = 'System';
                                if (isset($entry['user_name']) && !empty($entry['user_name'])) {
                                    $userName = (string)$entry['user_name'];
                                } elseif (isset($entry['picked_by_user_name']) && !empty($entry['picked_by_user_name'])) {
                                    $userName = (string)$entry['picked_by_user_name'];
                                }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div class="font-medium">{{ $formattedDate }}</div>
                                    @if($formattedTime)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $formattedTime }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($isPicked)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Picked
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            Unpicked
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ $itemDescription }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-medium">
                                    {{ number_format($quantity) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    @if(!empty($cartonNumber))
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Carton #{{ $cartonNumber }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $userName }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

