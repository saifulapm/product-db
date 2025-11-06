<x-filament-widgets::widget>
    <x-filament::section collapsed>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                    <span>DTF Tone on Tone Colors</span>
                </div>
                @php
                    $action = $this->getAction('edit_colors');
                    if (!$action) {
                        $actions = $this->getActions();
                        if (!empty($actions) && $actions[0] instanceof \Filament\Actions\Action) {
                            $action = $this->cacheAction($actions[0]);
                        }
                    }
                @endphp
                @if($action)
                    {{ $action }}
                @endif
            </div>
        </x-slot>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            @if(!empty($this->colors) && count($this->colors) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->colors as $color)
                        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                                {{ $color['name'] ?? 'Unnamed Color' }}
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        Tone on Tone (Darker)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-12 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm"
                                            style="background-color: {{ $color['darker'] ?? '#000000' }};"
                                            title="{{ $color['darker'] ?? '#000000' }}"
                                        ></div>
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                            {{ $color['darker'] ?? '#000000' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        Tone on Tone (Lighter)
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-12 h-12 rounded-lg border-2 border-gray-300 dark:border-gray-600 shadow-sm"
                                            style="background-color: {{ $color['lighter'] ?? '#f5f5f5' }};"
                                            title="{{ $color['lighter'] ?? '#f5f5f5' }}"
                                        ></div>
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                            {{ $color['lighter'] ?? '#f5f5f5' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                    No tone on tone colors added yet. Click "Edit Colors" to add colors.
                </p>
            @endif
        </div>
    </x-filament::section>
    
    {{-- Render modal for widget actions --}}
    <x-filament-actions::modals />
</x-filament-widgets::widget>

