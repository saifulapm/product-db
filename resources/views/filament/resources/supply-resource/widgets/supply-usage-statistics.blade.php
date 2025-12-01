<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Inventory Usage Statistics
        </x-slot>
        <div>
            @php
                $stats = $this->getUsageStatistics();
            @endphp
            
            @if($stats['total_used'] == 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No usage data available yet. Statistics will appear here once shipments are committed.
                </p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Total Used
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($stats['total_used']) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $stats['total_used'] == 1 ? 'piece' : 'pieces' }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Average Per Month
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($stats['average_per_month'], 1) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $stats['months_tracked'] }} {{ $stats['months_tracked'] == 1 ? 'month' : 'months' }} tracked
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                            Usage Period
                        </div>
                        <div class="text-sm text-gray-900 dark:text-white">
                            @if($stats['first_shipment'] && $stats['last_shipment'])
                                {{ $stats['first_shipment'] }}<br>
                                <span class="text-xs text-gray-500 dark:text-gray-400">to</span><br>
                                {{ $stats['last_shipment'] }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($stats['monthly_breakdown']))
                    <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden bg-white dark:bg-gray-900">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Monthly Breakdown
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($stats['monthly_breakdown'] as $month => $count)
                                @php
                                    $date = \Carbon\Carbon::createFromFormat('Y-m', $month);
                                @endphp
                                <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $date->format('F Y') }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($count) }} {{ $count == 1 ? 'piece' : 'pieces' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

