<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Headwear Team Notes</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Share launch plans, approved blanks, or sourcing reminders.</p>
                </div>
                @if($this->isEditable)
                    {{ $this->getAction('edit_notes') }}
                @endif
            </div>
        </x-slot>

        <div class="prose max-w-none dark:prose-invert">
            @if(empty($this->content))
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No notes yet. Click “Edit Notes” to add guidance for the team.</p>
            @else
                {!! $this->content !!}
            @endif
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>

