<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Shipment Contents
        </x-slot>

        @php
            // Check path directly - most reliable method
            $path = request()->path();
            $isEditPage = str_contains($path, '/edit');
            
            // Use the widget's isEditable property OR check path directly
            $isEditable = ($this->isEditable ?? false) || $isEditPage;
            
            // Always allow receiving quantities (on both view and edit pages)
            $canReceiveQty = $this->canReceiveQty ?? true;
            
            // Check if any items have been saved (to show Received QTY column)
            $hasSavedItems = false;
            if (!empty($this->items)) {
                foreach ($this->items as $item) {
                    if (!empty($item['is_saved']) && $item['is_saved'] === true) {
                        $hasSavedItems = true;
                        break;
                    }
                }
            }
        @endphp

        @if(empty($this->items))
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p>No items added to this shipment.</p>
                @if($isEditable)
                    <button
                        type="button"
                        wire:click="addItem"
                        class="mt-4 px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                    >
                        + Add Sock
                    </button>
                @endif
            </div>
        @else
            <div class="space-y-4">
                @if(($this->isEditable || $isEditable) && !empty($this->selectedRows))
                    <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-medium text-primary-900 dark:text-primary-100">
                                    {{ count($this->selectedRows) }} row(s) selected
                                </span>
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="px-3 py-1.5 text-sm font-medium text-primary-700 bg-primary-100 border border-primary-300 rounded hover:bg-primary-200 dark:bg-primary-800 dark:text-primary-200 dark:border-primary-700 dark:hover:bg-primary-700 transition-colors"
                                    >
                                        Bulk Update
                                        <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        @click.away="open = false"
                                        x-transition
                                        class="absolute z-10 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4"
                                    >
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Carton #</label>
                                                <input
                                                    type="text"
                                                    wire:model="bulkUpdateData.carton_number"
                                                    class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                                    placeholder="Leave empty to skip"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Order Number</label>
                                                <input
                                                    type="text"
                                                    wire:model="bulkUpdateData.order_number"
                                                    class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                                    placeholder="Leave empty to skip"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">QTY</label>
                                                <input
                                                    type="text"
                                                    wire:model="bulkUpdateData.quantity"
                                                    class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                                    placeholder="Leave empty to skip"
                                                />
                                            </div>
                                            <div class="flex gap-2">
                                                <button
                                                    type="button"
                                                    wire:click="bulkUpdateSelected"
                                                    @click="open = false"
                                                    class="flex-1 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 rounded hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 transition-colors"
                                                >
                                                    Update
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="open = false"
                                                    class="flex-1 px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="$set('selectedRows', [])"
                                    class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                                >
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @if($isEditable)
                                    <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider w-12">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectAll"
                                            class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                        />
                                    </th>
                                @endif
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 8%;">
                                    Carton #
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 12%;">
                                    Order Number
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 35%;">
                                    Product Name
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 8%;">
                                    QTY
                                </th>
                                @if($hasSavedItems)
                                    <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 10%;">
                                        Received QTY
                                    </th>
                                @endif
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 20%;">
                                    Tracking Number
                                </th>
                                @if($isEditable)
                                    <th class="px-2 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider w-12">
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->items as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @if($isEditable)
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selectedRows"
                                                value="{{ $index }}"
                                                class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </td>
                                    @endif
                                    <td class="px-2 py-2 whitespace-nowrap" style="width: 8%;">
                                        @if($isEditable)
                                            <input
                                                type="text"
                                                wire:model.live="items.{{ $index }}.carton_number"
                                                class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                style="font-size: 9pt;"
                                                placeholder=""
                                                required
                                            />
                                        @else
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $item['carton_number'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap" style="width: 12%;">
                                        @if($isEditable)
                                            <input
                                                type="text"
                                                wire:model.live="items.{{ $index }}.order_number"
                                                class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                style="font-size: 9pt;"
                                                placeholder=""
                                                required
                                            />
                                        @else
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $item['order_number'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2" style="width: 35%;">
                                        @if($isEditable)
                                            <input
                                                type="text"
                                                wire:model.live="items.{{ $index }}.product_name"
                                                class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                style="font-size: 9pt;"
                                                placeholder=""
                                                required
                                            />
                                        @else
                                            @php
                                                // Use product_name if available, otherwise construct from style/color
                                                $productName = $item['product_name'] ?? '';
                                                if (empty($productName)) {
                                                    $style = $item['style'] ?? '';
                                                    $color = $item['color'] ?? '';
                                                    if (!empty($style) && !empty($color)) {
                                                        $productName = $style . ' - ' . $color;
                                                    } elseif (!empty($style)) {
                                                        $productName = $style;
                                                    } elseif (!empty($color)) {
                                                        $productName = $color;
                                                    }
                                                }
                                            @endphp
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $productName ?: '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap" style="width: 8%;">
                                        @if($isEditable)
                                            <input
                                                type="text"
                                                wire:model.live="items.{{ $index }}.quantity"
                                                class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                style="font-size: 9pt;"
                                                placeholder=""
                                                required
                                            />
                                        @else
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $item['quantity'] ?? 0 }}</span>
                                        @endif
                                    </td>
                                    @if(!empty($item['is_saved']) && $item['is_saved'] === true)
                                        <td class="px-2 py-2 whitespace-nowrap" style="width: 10%;">
                                            @if($canReceiveQty)
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        wire:model.live="items.{{ $index }}.received_qty"
                                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm @if((int)($item['received_qty'] ?? 0) > (int)($item['quantity'] ?? 0)) border-yellow-400 dark:border-yellow-600 @endif"
                                                        style="font-size: 9pt;"
                                                        placeholder="0"
                                                        @if((int)($item['received_qty'] ?? 0) > (int)($item['quantity'] ?? 0))
                                                            title="Over by {{ (int)($item['received_qty'] ?? 0) - (int)($item['quantity'] ?? 0) }}"
                                                        @endif
                                                    />
                                                    @if(($item['quantity'] ?? 0) > 0)
                                                        <button
                                                            type="button"
                                                            wire:click="receiveFullQty({{ $index }})"
                                                            class="px-2 py-1 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors whitespace-nowrap"
                                                            title="Receive full quantity"
                                                        >
                                                            Full
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <span 
                                                    class="text-sm text-gray-900 dark:text-white cursor-help"
                                                    @if((int)($item['received_qty'] ?? 0) > (int)($item['quantity'] ?? 0))
                                                        title="Over by {{ (int)($item['received_qty'] ?? 0) - (int)($item['quantity'] ?? 0) }}"
                                                    @endif
                                                >
                                                    {{ $item['received_qty'] ?? 0 }}
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-2 py-2 whitespace-nowrap" style="width: 20%;">
                                        @if($isEditable)
                                            <input
                                                type="text"
                                                wire:model.live="items.{{ $index }}.eid"
                                                class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                style="font-size: 9pt;"
                                                placeholder=""
                                                required
                                            />
                                        @else
                                            <span class="text-sm text-gray-900 dark:text-white">{{ $item['eid'] ?? '—' }}</span>
                                        @endif
                                    </td>
                                    @if($isEditable)
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <button
                                                type="button"
                                                wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                                                title="Delete row"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Use widget's stored isEditPage property or check widget's isEditable --}}
                @if(($this->isEditPage ?? false) || ($this->isEditable ?? false))
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            wire:click="addItem"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                        >
                            + Add Sock
                        </button>
                        <div class="flex items-center gap-3">
                            <a
                                href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('index') }}"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </a>
                            <button
                                type="button"
                                onclick="
                                    // Sync items first
                                    @this.call('syncItemsToForm');
                                    // Wait for sync to complete, then submit the Filament form
                                    setTimeout(() => {
                                        const form = document.querySelector('form[wire\\:submit]');
                                        if (form) {
                                            form.requestSubmit();
                                            // Redirect after a short delay to allow save to complete
                                            setTimeout(() => {
                                                const viewUrl = '{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('view', ['record' => $this->record]) }}';
                                                window.location.href = viewUrl;
                                            }, 500);
                                        }
                                    }, 300);
                                "
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
