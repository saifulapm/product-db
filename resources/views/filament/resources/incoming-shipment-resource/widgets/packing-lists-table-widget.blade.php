<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Packing Lists
        </x-slot>

        @php
            $pickLists = $this->getViewData()['pickLists'] ?? [];
        @endphp

        @if(empty($pickLists))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm">No packing lists available.</p>
            </div>
        @else
            <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/10">
                        <thead class="divide-y divide-gray-200 dark:divide-white/10">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Name
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    File
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Uploaded
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Items
                                </th>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Progress
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/10">
                            @foreach($pickLists as $pickList)
                                @php
                                    $uploadedAtFormatted = '';
                                    if ($pickList['uploaded_at']) {
                                        try {
                                            $uploadedAtFormatted = \Carbon\Carbon::parse($pickList['uploaded_at'])->format('M d, Y g:i A');
                                        } catch (\Exception $e) {
                                            $uploadedAtFormatted = $pickList['uploaded_at'];
                                        }
                                    }
                                    
                                    $statusColor = match($pickList['status']) {
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                                    };
                                    
                                    $statusLabel = match($pickList['status']) {
                                        'completed' => 'Completed',
                                        'in_progress' => 'In Progress',
                                        default => 'Pending',
                                    };
                                @endphp
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-3 text-sm">
                                        <a 
                                            href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('view-pick-list', [
                                                'shipmentId' => $this->record->id,
                                                'pickListIndex' => $pickList['index'],
                                            ]) }}"
                                            class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium hover:underline"
                                        >
                                            {{ $pickList['name'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                                        {{ $pickList['filename'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                                        {{ $uploadedAtFormatted ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs font-medium rounded {{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                                        {{ number_format($pickList['item_count']) }} items
                                        <br>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($pickList['total_needed']) }} needed
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div 
                                                    class="bg-primary-600 h-2 rounded-full transition-all" 
                                                    style="width: {{ $pickList['progress_percent'] }}%"
                                                ></div>
                                            </div>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 min-w-[3rem]">
                                                {{ $pickList['progress_percent'] }}%
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ number_format($pickList['total_picked']) }} / {{ number_format($pickList['total_needed']) }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

