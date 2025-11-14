<x-filament-widgets::widget>
    <div class="widget-content">
        <x-filament::section>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Team Notes</p>
                    @if($this->isEditable)
                        {{ $this->getAction('edit_notes') }}
                    @endif
                </div>
            </x-slot>
            <div class="prose max-w-none dark:prose-invert">
                @if(empty($this->content))
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No notes available.</p>
                @else
                    {!! $this->content !!}
                @endif
            </div>
        </x-filament::section>
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>

