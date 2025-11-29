<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Shipment Contents
        </x-slot>
        <div class="space-y-6">
            {{-- Bulk Actions --}}
            @if(!empty($this->selectedRows))
                <div class="flex items-center gap-2 p-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-lg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ count($this->selectedRows) }} row(s) selected
                    </span>
                    <div
                        x-data="{ open: false }"
                        class="relative"
                    >
                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="px-3 py-1.5 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 transition-colors"
                        >
                            Bulk Update
                        </button>
                        <div
                            x-show="open"
                            x-on:click.away="open = false"
                            x-cloak
                            class="absolute top-full left-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 p-4"
                        >
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Update Selected Rows</h3>
                            <form wire:submit.prevent="bulkUpdateSelected">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Carton #</label>
                                        <input
                                            type="text"
                                            wire:model="bulkUpdateData.carton_number"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                            placeholder="Leave empty to skip"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Order Number</label>
                                        <input
                                            type="text"
                                            wire:model="bulkUpdateData.order_number"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                            placeholder="Leave empty to skip"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">QTY</label>
                                        <input
                                            type="text"
                                            wire:model="bulkUpdateData.quantity"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                            placeholder="Leave empty to skip"
                                        />
                                    </div>
                                    <div class="flex gap-2 pt-2">
                                        <button
                                            type="submit"
                                            x-on:click="open = false"
                                            class="flex-1 px-3 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 transition-colors"
                                        >
                                            Update
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="open = false"
                                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </form>
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
            @endif

            {{-- Carton Entry Table --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider w-12">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 10%;">
                                Carton #
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 15%;">
                                Order Number
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 25%;">
                                Ethos ID
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 40%;">
                                Product Name
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 10%;">
                                QTY
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider w-12">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->cartons as $index => $carton)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedRows"
                                        value="{{ $index }}"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="width: 10%;">
                                    <input
                                        type="text"
                                        wire:model.live="cartons.{{ $index }}.carton_number"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="width: 15%;">
                                    <input
                                        type="text"
                                        wire:model.live="cartons.{{ $index }}.order_number"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="width: 25%;">
                                    <input
                                        type="text"
                                        wire:model.live="cartons.{{ $index }}.eid"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="px-4 py-3" style="width: 40%;">
                                    <input
                                        type="text"
                                        wire:model.live="cartons.{{ $index }}.product_name"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" style="width: 10%;">
                                    <input
                                        type="text"
                                        wire:model.live="cartons.{{ $index }}.quantity"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <button
                                        type="button"
                                        wire:click="removeCarton({{ $index }})"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                                        title="Delete row"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    wire:click="addCarton"
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline font-medium"
                >
                    Add Sock
                </button>
                <div class="flex gap-2">
                    <a
                        href="{{ \App\Filament\Resources\IncomingShipmentResource::getUrl('index') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancel
                    </a>
                    <button
                        type="button"
                        wire:click="syncCartonsToForm"
                        onclick="
                            const form = document.querySelector('form[wire\\:submit]');
                            if (form) {
                                setTimeout(() => {
                                    form.requestSubmit();
                                }, 300);
                            }
                        "
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 transition-colors"
                    >
                        Create
                    </button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

