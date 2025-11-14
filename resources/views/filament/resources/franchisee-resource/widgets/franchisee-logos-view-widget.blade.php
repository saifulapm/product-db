<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Franchisee Logos
        </x-slot>
        <x-slot name="description">
            Download any of the logos associated with this franchisee
        </x-slot>
        
        @php
            $logos = $this->getLogos();
        @endphp

        @if(empty($logos))
            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No logos uploaded for this franchisee.</p>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach($logos as $logo)
                    <div class="flex flex-col items-center space-y-2 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <img
                            src="{{ $logo['url'] }}"
                            alt="{{ $logo['label'] }}"
                            class="w-full h-32 object-contain"
                        />
                        <div class="text-xs text-gray-600 dark:text-gray-300 font-medium text-center">
                            {{ $logo['filename'] }}
                        </div>
                        <a
                            href="{{ $logo['url'] }}"
                            download="{{ $logo['filename'] }}"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

