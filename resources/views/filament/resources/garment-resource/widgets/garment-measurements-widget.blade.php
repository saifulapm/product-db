<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Garment Measurements
        </x-slot>
        <div class="space-y-4">
            {{-- Fabric Panels List --}}
            @if(empty($this->measurements))
                <div class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                    No fabric panels added yet. Click "Add Fabric Panel" to get started.
                </div>
            @else
                @foreach($this->measurements as $panelIndex => $panel)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                        {{-- Panel Header --}}
                        <div 
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:click="togglePanel({{ $panelIndex }})"
                        >
                            <div class="flex items-center gap-3 flex-1">
                                <svg 
                                    class="w-5 h-5 text-gray-400 transition-transform {{ $this->isPanelExpanded($panelIndex) ? 'rotate-90' : '' }}"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <input
                                    type="text"
                                    wire:model.live="measurements.{{ $panelIndex }}.fabric_panel_name"
                                    wire:click.stop
                                    class="flex-1 rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm font-medium"
                                    placeholder="Enter fabric panel name..."
                                />
                            </div>
                            <button
                                type="button"
                                wire:click.stop="removeFabricPanel({{ $panelIndex }})"
                                class="ml-4 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                title="Remove fabric panel"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                        {{-- Expanded Panel Content --}}
                        @if($this->isPanelExpanded($panelIndex))
                            <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-4">
                                {{-- Image URL Input --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Fabric Panel Image URL
                                    </label>
                                    <input
                                        type="url"
                                        wire:model.live="measurements.{{ $panelIndex }}.image_url"
                                        class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                        placeholder="https://example.com/image.jpg"
                                    />
                                </div>

                                {{-- Image Display --}}
                                @if(!empty($panel['image_url']))
                                    <div class="relative border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800">
                                        <img 
                                            src="{{ $panel['image_url'] }}" 
                                            alt="{{ $panel['fabric_panel_name'] ?? 'Fabric Panel' }}"
                                            class="w-full h-auto max-h-96 object-contain"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                        />
                                        <div style="display: none;" class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Failed to load image. Please check the URL.
                                        </div>
                                    </div>
                                @endif

                                {{-- Measurements Table --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Mapped Measurements
                                    </label>
                                    <div class="border border-gray-200 dark:border-gray-700 rounded overflow-hidden">
                                        <table class="w-full">
                                            <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                                        Measurement
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                                        Value (inches)
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wider">
                                                        Value (cm)
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            wire:model.live="measurements.{{ $panelIndex }}.length_label"
                                                            class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                            placeholder="e.g., Front Length"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input
                                                            type="number"
                                                            wire:model.live="measurements.{{ $panelIndex }}.length_value"
                                                            step="0.01"
                                                            min="0"
                                                            class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                            placeholder="0.00"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <div class="block w-full rounded border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-800 dark:text-white sm:text-sm px-3 py-2">
                                                            @php
                                                                $inches = $panel['length_value'] ?? '';
                                                                $cm = $this->convertInchesToCm($inches);
                                                            @endphp
                                                            {{ $cm !== null ? number_format($cm, 2) : '—' }}
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input
                                                            type="text"
                                                            wire:model.live="measurements.{{ $panelIndex }}.width_label"
                                                            class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                            placeholder="e.g., Chest Width"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <input
                                                            type="number"
                                                            wire:model.live="measurements.{{ $panelIndex }}.width_value"
                                                            step="0.01"
                                                            min="0"
                                                            class="block w-full rounded border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                                                            placeholder="0.00"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        <div class="block w-full rounded border-gray-300 shadow-sm bg-gray-100 dark:bg-gray-800 dark:text-white sm:text-sm px-3 py-2">
                                                            @php
                                                                $inches = $panel['width_value'] ?? '';
                                                                $cm = $this->convertInchesToCm($inches);
                                                            @endphp
                                                            {{ $cm !== null ? number_format($cm, 2) : '—' }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- Footer Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    wire:click="addFabricPanel"
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 underline font-medium"
                >
                    + Add Fabric Panel
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
