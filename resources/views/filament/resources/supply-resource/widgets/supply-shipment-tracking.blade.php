<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Shipment Tracking
        </x-slot>
        <div>
            @php
                $tracking = $this->getShipmentTracking();
            @endphp
            
            @if(empty($tracking))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No shipments tracked yet. Shipments will appear here when committed from the "Create A Shipment" page.
                </p>
            @else
                <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Order Number
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Date Used
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($tracking as $shipment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $shipment['order_number'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            @if(!empty($shipment['used_at']))
                                                {{ \Carbon\Carbon::parse($shipment['used_at'])->format('M d, Y g:i A') }}
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

