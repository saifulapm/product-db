<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Garment Measurements
        </x-slot>
        <div class="space-y-4">
            @php
                $measurements = $this->getMeasurements();
            @endphp
            
            @if(empty($measurements))
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                    No fabric panels added yet.
                </p>
            @else
                @foreach($measurements as $panelIndex => $panel)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                        {{-- Panel Header --}}
                        <div 
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            wire:click="togglePanel({{ $panelIndex }})"
                        >
                            <div class="flex items-center gap-3">
                                <svg 
                                    class="w-5 h-5 text-gray-400 transition-transform {{ $this->isPanelExpanded($panelIndex) ? 'rotate-90' : '' }}"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $panel['fabric_panel_name'] ?? 'Unnamed Panel' }}
                                </span>
                            </div>
                        </div>

                        {{-- Expanded Panel Content --}}
                        @if($this->isPanelExpanded($panelIndex))
                            <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-4">
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
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {{ $panel['length_label'] ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        @php
                                                            $inches = $panel['length_value'] ?? '';
                                                        @endphp
                                                        {{ !empty($inches) && is_numeric($inches) ? number_format((float)$inches, 2) : '—' }}
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        @php
                                                            $inches = $panel['length_value'] ?? '';
                                                            $cm = !empty($inches) && is_numeric($inches) ? round((float)$inches * 2.54, 2) : null;
                                                        @endphp
                                                        {{ $cm !== null ? number_format($cm, 2) : '—' }}
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {{ $panel['width_label'] ?? '—' }}
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        @php
                                                            $inches = $panel['width_value'] ?? '';
                                                        @endphp
                                                        {{ !empty($inches) && is_numeric($inches) ? number_format((float)$inches, 2) : '—' }}
                                                    </td>
                                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        @php
                                                            $inches = $panel['width_value'] ?? '';
                                                            $cm = !empty($inches) && is_numeric($inches) ? round((float)$inches * 2.54, 2) : null;
                                                        @endphp
                                                        {{ $cm !== null ? number_format($cm, 2) : '—' }}
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
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
