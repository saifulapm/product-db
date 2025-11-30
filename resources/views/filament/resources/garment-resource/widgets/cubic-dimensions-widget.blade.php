<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Cubic Dimensions
        </x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Length (inches)
                    </label>
                    <input
                        type="number"
                        wire:model.live="length"
                        step="0.01"
                        min="0"
                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        placeholder="0.00"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Width (inches)
                    </label>
                    <input
                        type="number"
                        wire:model.live="width"
                        step="0.01"
                        min="0"
                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        placeholder="0.00"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Height (inches)
                    </label>
                    <input
                        type="number"
                        wire:model.live="height"
                        step="0.01"
                        min="0"
                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                        placeholder="0.00"
                    />
                </div>
            </div>

            @php
                $volume = $this->getCubicVolume();
            @endphp

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
    </x-filament::section>
</x-filament-widgets::widget>

