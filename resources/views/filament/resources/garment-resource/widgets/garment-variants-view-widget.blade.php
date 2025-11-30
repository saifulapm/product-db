<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Variants Summary
        </x-slot>
        <div class="space-y-6">
            @php
                $summary = $this->getVariantsSummary();
                $variants = $this->getVariants();
            @endphp
            
            @if(empty($summary))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No variants added yet.
                </p>
            @else
                <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Variant
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Total Quantity
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($summary as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $item['variant_name'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($item['total_quantity']) }}
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

    <div style="padding: 0 10px; margin: 10px 0;">
        <div class="border-t border-gray-300 dark:border-gray-600" style="height: 1px; width: 100%; background-color: rgb(209, 213, 219);"></div>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Variants
        </x-slot>
        <div>
            @if(empty($variants))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No variants added yet.
                </p>
            @else
                <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 25%;">
                                    Variant
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 25%;">
                                    Ethos ID
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 15%;">
                                    Inventory
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 15%;">
                                    Shelf #
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider" style="width: 20%;">
                                    Total Inventory
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($variants as $variant)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 whitespace-nowrap" style="width: 25%;">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $variant['name'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" style="width: 25%;">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $variant['sku'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" style="width: 15%;">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $variant['inventory'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" style="width: 15%;">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $variant['shelf_number'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" style="width: 20%;">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $variant['total_inventory'] ?? 0 }}
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

