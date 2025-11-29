<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Shipment Timeline
        </x-slot>

        @php
            $events = $this->getTimelineEvents();
        @endphp

        @if(empty($events))
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <p>No timeline events yet.</p>
            </div>
        @else
            <div class="relative">
                <!-- Timeline line -->
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                <div class="space-y-6">
                    @foreach($events as $event)
                        <div class="relative flex items-start gap-4">
                            <!-- Icon -->
                            @php
                                $colorClasses = match($event['color']) {
                                    'primary' => 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400',
                                    'info' => 'bg-info-100 dark:bg-info-900 text-info-600 dark:text-info-400',
                                    'success' => 'bg-success-100 dark:bg-success-900 text-success-600 dark:text-success-400',
                                    'warning' => 'bg-warning-100 dark:bg-warning-900 text-warning-600 dark:text-warning-400',
                                    default => 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400',
                                };
                            @endphp
                            <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full {{ $colorClasses }}">
                                @if($event['icon'] === 'heroicon-o-plus-circle')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                @elseif($event['icon'] === 'heroicon-o-truck')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                @elseif($event['icon'] === 'heroicon-o-inbox-arrow-down')
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $event['title'] }}
                                    </h3>
                                    <time class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $event['timestamp']->format('M d, Y g:i A') }}
                                    </time>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $event['description'] }}
                                </p>

                                @if(isset($event['items']) && !empty($event['items']))
                                    <div class="mt-3 space-y-2">
                                        @foreach($event['items'] as $item)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded px-2 py-1">
                                                <span class="font-medium">Carton {{ $item['carton'] }}</span>:
                                                {{ $item['product'] }} - 
                                                Received {{ $item['received'] }} of {{ $item['quantity'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
