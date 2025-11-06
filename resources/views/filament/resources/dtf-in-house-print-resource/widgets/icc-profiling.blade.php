<x-filament-widgets::widget>
    <x-filament::section collapsed>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <span>ICC Profiling</span>
                @php
                    $action = $this->getAction('edit_content');
                    if (!$action) {
                        $action = $this->editContent();
                        $this->cacheAction($action);
                    }
                @endphp
                @if($action)
                    {{ $action }}
                @endif
            </div>
        </x-slot>
        
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            @if(!empty($this->content))
                <div class="prose max-w-none dark:prose-invert mb-4">
                    {!! $this->content !!}
                </div>
            @endif

            @if(!empty($this->existingImages))
                <div class="space-y-4 mt-4">
                    @foreach($this->existingImages as $index => $image)
                        <div class="relative group">
                            <img src="{{ $image }}" 
                                 alt="ICC Profiling Image {{ $index + 1 }}"
                                 class="w-full h-auto object-contain rounded-lg shadow-md">
                            <button 
                                wire:click="removeImage({{ $index }})"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                onclick="return confirm('Are you sure you want to remove this image?')"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(empty($this->content) && empty($this->existingImages))
                <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                    No content yet. Click "Add Content" to get started.
                </p>
            @endif
        </div>
        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>

