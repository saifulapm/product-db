<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Cubic Dimensions
        </x-slot>
        <div>
            @php
                $dimensions = $this->getCubicDimensions();
                $volume = $this->getCubicVolume();
            @endphp
            
            @if(!$dimensions || (empty($dimensions['length']) && empty($dimensions['width']) && empty($dimensions['height'])))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No cubic dimensions added yet.
                </p>
            @else
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Length (inches)
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $dimensions['length'] ? number_format((float)$dimensions['length'], 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Width (inches)
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $dimensions['width'] ? number_format((float)$dimensions['width'], 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Height (inches)
                            </label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $dimensions['height'] ? number_format((float)$dimensions['height'], 2) : '—' }}
                            </p>
                        </div>
                    </div>

                    @if($volume !== null)
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Cubic Volume</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                        {{ number_format($volume, 2) }} in³
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Cubic Volume (cm³)</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                        {{ number_format($volume * 16.387, 2) }} cm³
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

