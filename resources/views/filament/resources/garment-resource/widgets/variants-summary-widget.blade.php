<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Variants Summary
        </x-slot>
        <div>
            @php
                $summary = $this->getVariantsSummary();
            @endphp
            
            @if(empty($summary))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No variants added yet. Add variants below to see summary.
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
</x-filament-widgets::widget>
<div style="padding: 0 10px; margin: 10px 0;">
    <div style="height: 1px; width: 100%; background-color: rgb(209, 213, 219); border-top: 1px solid rgb(209, 213, 219);"></div>
</div>

