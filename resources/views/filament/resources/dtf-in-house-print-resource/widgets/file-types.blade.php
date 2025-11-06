<x-filament-widgets::widget>
    <x-filament::section collapsed>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <span>DTF File Types</span>
                @php
                    $action = $this->getAction('edit_content');
                    // If action not found, ensure it's cached
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
                <div class="prose max-w-none dark:prose-invert">
                    {!! $this->content !!}
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                    No content yet. Click "Add Content" to get started.
                </p>
            @endif
        </div>
        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>

