<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-4">
                <span>Incoming Items</span>
                <div class="flex gap-2 flex-wrap">
                    {{ $this->addProductAction() }}
                    {{ $this->bulkAddProductsAction() }}
                </div>
            </div>
        </x-slot>

        <x-filament::section>
            @if(!empty($this->selectedItems))
                <div class="mb-4">
                    {{ $this->bulkActionsGroup() }}
                </div>
            @endif

            @if(empty($this->getViewData()['items']))
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No items added yet. Click "Add Product" to get started.
                </div>
            @else
                <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/10">
                            <thead class="divide-y divide-gray-200 dark:divide-white/10">
                                <tr class="bg-gray-50 dark:bg-white/5">
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white w-12">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectAll"
                                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                        Product
                                    </th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                        CTN#
                                    </th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                        Quantity
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/10">
                                @foreach($this->getViewData()['items'] as $item)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selectedItems"
                                                value="{{ $item['index'] }}"
                                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if(!empty($item['product_id']))
                                                <a 
                                                    href="{{ \App\Filament\Resources\SockStyleResource::getUrl('view', ['record' => $item['product_id']]) }}"
                                                    target="_blank"
                                                    class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium hover:underline"
                                                >
                                                    {{ $item['product_name'] ?? 'N/A' }}
                                                </a>
                                            @else
                                                <span class="text-gray-950 dark:text-white">{{ $item['product_name'] ?? 'N/A' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                                            {{ $item['carton_number'] ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">
                                            {{ $item['quantity'] ?? 0 }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </x-filament::section>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

