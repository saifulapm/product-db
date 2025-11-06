<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Gallery
        </x-slot>
        
        @if(!empty($galleryItems) && count($galleryItems) > 0)
            <div class="mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-4 gap-4">
                    @foreach($galleryItems as $index => $item)
                        @if(!empty($item['url']))
                            <div class="flex flex-col">
                                <div class="relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shadow-sm mb-2">
                                    <img 
                                        src="{{ $item['url'] }}" 
                                        alt="{{ $item['description'] ?: 'Reference image ' . ($index + 1) }}" 
                                        class="w-full h-full object-cover" 
                                        loading="lazy" 
                                    />
                                </div>
                                @if(!empty($item['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center mt-1">
                                        {{ $item['description'] }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">No gallery images added yet.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

