@php
    $pickLists = $this->getPickLists();
    $shipment = $this->shipment;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Uploaded Pick Lists
        </x-slot>
        
        <x-slot name="description">
            Manage all pick lists uploaded for this incoming shipment. Edit names, view details, or delete pick lists.
        </x-slot>
        
        @if(empty($pickLists))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm">No pick lists uploaded yet.</p>
                <p class="mt-1 text-xs text-gray-400">Upload pick lists using the "Upload Pick List" button above.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($pickLists as $pickListIndex => $pickList)
                    @php
                        $pickListName = $pickList['name'] ?? 'Pick List ' . ($pickListIndex + 1);
                        $fileName = $pickList['filename'] ?? 'Unknown';
                        $uploadedAt = $pickList['uploaded_at'] ?? '';
                        $status = $pickList['status'] ?? 'pending';
                        $items = $pickList['items'] ?? [];
                        $itemCount = count($items);
                        
                        // Calculate totals
                        $totalNeeded = 0;
                        foreach ($items as $item) {
                            $totalNeeded += $item['quantity'] ?? $item['quantity_required'] ?? 0;
                        }
                        
                        $pickedItems = $pickList['picked_items'] ?? [];
                        $totalPicked = 0;
                        foreach ($pickedItems as $picked) {
                            $totalPicked += $picked['quantity_picked'] ?? 0;
                        }
                        
                        $remaining = max(0, $totalNeeded - $totalPicked);
                        $progressPercent = $totalNeeded > 0 ? round(($totalPicked / $totalNeeded) * 100) : 0;
                        
                        // Format uploaded date
                        $uploadedAtFormatted = '';
                        if ($uploadedAt) {
                            try {
                                $uploadedAtFormatted = \Carbon\Carbon::parse($uploadedAt)->format('M d, Y g:i A');
                            } catch (\Exception $e) {
                                $uploadedAtFormatted = $uploadedAt;
                            }
                        }
                        
                        // Status badge
                        $statusColor = match($status) {
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                        };
                        
                        $statusLabel = match($status) {
                            'completed' => 'Completed',
                            'in_progress' => 'In Progress',
                            default => 'Pending',
                        };
                    @endphp
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-white dark:bg-gray-800 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <div 
                                        x-data="{ editing: false, name: '{{ addslashes($pickListName) }}' }"
                                        class="flex-1"
                                    >
                                        <div x-show="!editing" class="flex items-center gap-2">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $pickListName }}
                                            </h3>
                                            <button 
                                                @click="editing = true"
                                                class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                                                title="Edit name"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div x-show="editing" class="flex items-center gap-2">
                                            <input 
                                                type="text"
                                                x-model="name"
                                                @keyup.enter="
                                                    $wire.updatePickListName({{ $pickListIndex }}, name).then(() => editing = false)
                                                "
                                                @keyup.escape="editing = false; name = '{{ addslashes($pickListName) }}'"
                                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                                style="padding: 0.375rem 0.75rem;"
                                            />
                                            <button 
                                                @click="
                                                    $wire.updatePickListName({{ $pickListIndex }}, name).then(() => editing = false)
                                                "
                                                class="text-green-600 hover:text-green-700 dark:text-green-400"
                                                title="Save"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button 
                                                @click="editing = false; name = '{{ addslashes($pickListName) }}'"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                title="Cancel"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded {{ $statusColor }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 space-y-1">
                                    <div><strong>File:</strong> {{ $fileName }}</div>
                                    @if($uploadedAtFormatted)
                                        <div><strong>Uploaded:</strong> {{ $uploadedAtFormatted }}</div>
                                    @endif
                                </div>
                                
                                <div class="flex items-center gap-4 text-sm mb-3">
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <strong>{{ number_format($itemCount) }}</strong> items
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <strong>{{ number_format($totalNeeded) }}</strong> pcs needed
                                    </span>
                                    <span class="text-green-600 dark:text-green-400">
                                        <strong>{{ number_format($totalPicked) }}</strong> pcs picked
                                    </span>
                                    <span class="text-orange-600 dark:text-orange-400">
                                        <strong>{{ number_format($remaining) }}</strong> pcs remaining
                                    </span>
                                </div>
                                
                                @if($totalNeeded > 0)
                                    <div class="mt-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $progressPercent }}% complete</div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="ml-4 flex items-center gap-2">
                                @if($shipment)
                                <a 
                                    href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('view-pick-list', [
                                        'shipmentId' => $shipment->id,
                                        'pickListIndex' => $pickListIndex,
                                    ]) }}"
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                                >
                                    View
                                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                                @endif
                                <button 
                                    wire:click="deletePickList({{ $pickListIndex }})"
                                    wire:confirm="Are you sure you want to delete this pick list? This action cannot be undone."
                                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                                >
                                    Delete
                                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>


