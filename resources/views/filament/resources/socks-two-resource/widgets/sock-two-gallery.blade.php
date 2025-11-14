<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Gallery Images
        </x-slot>

        @if(empty($galleryItems))
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No gallery images added for this sock style.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($galleryItems as $item)
                    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                        <div class="aspect-video bg-gray-100 dark:bg-gray-800">
                            <img src="{{ $item['url'] }}" alt="{{ $item['description'] ?: 'Sock gallery image' }}" class="w-full h-full object-cover">
                        </div>
                        @if(!empty($item['description']))
                            <div class="p-3 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-700 dark:text-gray-300">
                                {{ $item['description'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

