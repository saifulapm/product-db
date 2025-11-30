<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Variants
        </x-slot>
        <div class="space-y-6">
            {{-- Bulk Actions --}}
            @if(!empty($this->selectedRows))
                <div class="flex items-center gap-2 p-4 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-lg">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ count($this->selectedRows) }} row(s) selected
                    </span>
                    <button
                        type="button"
                        wire:click="bulkDeleteSelected"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 border border-transparent rounded hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 transition-colors"
                    >
                        Delete Selected
                    </button>
                    <button
                        type="button"
                        wire:click="$set('selectedRows', [])"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                    >
                        Clear Selection
                    </button>
                </div>
            @endif

            {{-- Variant Entry Table --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider w-12" style="padding-left: 1rem; padding-right: 8px;">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                />
                            </th>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 25%; padding-left: 0; padding-right: 8px;">
                                Variant
                            </th>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 25%; padding-left: 8px; padding-right: 8px;">
                                Ethos ID
                            </th>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 15%; padding-left: 8px; padding-right: 8px;">
                                Inventory
                            </th>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 15%; padding-left: 8px; padding-right: 8px;">
                                Shelf #
                            </th>
                            <th class="py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 20%; padding-left: 8px; padding-right: 1rem;">
                                Total Inventory
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->variants as $index => $variant)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3 whitespace-nowrap" style="padding-left: 1rem; padding-right: 8px;">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedRows"
                                        value="{{ $index }}"
                                        class="w-4 h-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                                    />
                                </td>
                                <td class="py-3 whitespace-nowrap" style="width: 25%; padding-left: 0; padding-right: 8px;">
                                    <input
                                        type="text"
                                        wire:model.live="variants.{{ $index }}.name"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="py-3 whitespace-nowrap" style="width: 25%; padding-left: 8px; padding-right: 8px;">
                                    <input
                                        type="text"
                                        wire:model.live="variants.{{ $index }}.sku"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder=""
                                    />
                                </td>
                                <td class="py-3 whitespace-nowrap" style="width: 15%; padding-left: 8px; padding-right: 8px;">
                                    <input
                                        type="number"
                                        wire:model.live="variants.{{ $index }}.inventory"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder="0"
                                        min="0"
                                    />
                                </td>
                                <td class="py-3 whitespace-nowrap" style="width: 15%; padding-left: 8px; padding-right: 8px;">
                                    <select
                                        wire:model.live="variants.{{ $index }}.shelf_number"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                    >
                                        <option value="">Select Shelf</option>
                                        @foreach($this->getShelvesOptions() as $shelfName => $shelfLabel)
                                            <option value="{{ $shelfName }}">{{ $shelfLabel }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-3 whitespace-nowrap" style="width: 20%; padding-left: 8px; padding-right: 1rem;">
                                    <input
                                        type="number"
                                        wire:model="variants.{{ $index }}.total_inventory"
                                        readonly
                                        class="block w-full rounded border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-800 dark:text-white sm:text-sm cursor-not-allowed"
                                        placeholder="0"
                                    />
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
                    wire:click="addVariant"
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline font-medium"
                >
                    + Add Variant
                </button>
                <div class="flex gap-2">
                    <a
                        href="{{ \App\Filament\Resources\GarmentResource::getUrl('index') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancel
                    </a>
                    <button
                        type="button"
                        wire:click="dispatchVariantsToParent"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 transition-colors"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
