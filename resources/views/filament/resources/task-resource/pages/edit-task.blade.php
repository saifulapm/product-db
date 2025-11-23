@php
    $record = $this->getRecord();
    $isSubtask = !empty($record->parent_task_id);
    
    // Build breadcrumbs
    $breadcrumbs = [];
    $breadcrumbs[] = [
        'label' => 'Dashboard',
        'url' => \Filament\Facades\Filament::getUrl(),
    ];
    $breadcrumbs[] = [
        'label' => 'Tasks',
        'url' => \App\Filament\Resources\TaskResource::getUrl('index'),
    ];
    
    // If subtask, add parent task
    if ($isSubtask && $record->parentTask) {
        $breadcrumbs[] = [
            'label' => $record->parentTask->title,
            'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $record->parentTask]),
        ];
        $breadcrumbs[] = [
            'label' => $record->title,
            'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $record]),
        ];
    } else {
        // For main tasks, add view link
        $breadcrumbs[] = [
            'label' => $record->title,
            'url' => \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $record]),
        ];
    }
    
    // Add current page (Edit)
    $breadcrumbs[] = [
        'label' => 'Edit',
        'url' => null,
    ];
@endphp

<x-filament-panels::page>
    {{-- Custom Breadcrumbs --}}
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                @foreach($breadcrumbs as $index => $breadcrumb)
                    <li class="inline-flex items-center">
                        @if($index > 0)
                            <svg class="w-6 h-6 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                        @if($breadcrumb['url'])
                            <a href="{{ $breadcrumb['url'] }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                                @if($index === 0)
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                @endif
                                {{ $breadcrumb['label'] }}
                            </a>
                        @else
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                                {{ $breadcrumb['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
    
    <form wire:submit="save">
        {{ $this->form }}
        
        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </form>
</x-filament-panels::page>

