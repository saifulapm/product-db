<x-filament-panels::page>
    <style>
        /* Global CSS to make subtasks section full width */
        [data-section="Subtasks"] .fi-section-content-ctn,
        [data-section="Subtasks"] .fi-section-content,
        [data-section="Subtasks"] [data-field-wrapper],
        [data-section="Subtasks"] .fi-fo-field-wrp,
        [data-section="Subtasks"] .fi-fo-field-wrp-label-ctn,
        .subtasks-section-full-width .fi-section-content-ctn,
        .subtasks-section-full-width .fi-section-content,
        .subtasks-section-full-width [data-field-wrapper] {
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        /* Make the subtasks container break out */
        .subtasks-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .subtasks-container > div {
            width: 100% !important;
            max-width: 100% !important;
        }
    </style>
    
    @php
        $record = $this->getRecord();
        $isSubtask = !empty($record->parent_task_id);
        $hasSubtasks = !$isSubtask && $record->subtasks()->count() > 0;
        
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
        }
        
        // Add current task/subtask (non-clickable)
        $breadcrumbs[] = [
            'label' => $record->title,
            'url' => null,
        ];
    @endphp
    
    <div>
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
        
        {{ $this->infolist }}
        
        @if($hasSubtasks)
        <div id="subtasks-widget-container" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6" style="display: none;">
            <div class="fi-section-header-ctn px-6 py-4">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Subtasks
                </h3>
            </div>
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    @livewire(\App\Filament\Resources\TaskResource\Widgets\SubtasksTableWidget::class)
                </div>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const subtasksContainer = document.getElementById('subtasks-widget-container');
                if (!subtasksContainer) return;
                
                // Try to find Attachments section first
                const attachmentsSection = document.querySelector('[data-section="Attachments"]');
                if (attachmentsSection) {
                    // Insert subtasks after Attachments section
                    attachmentsSection.parentNode.insertBefore(subtasksContainer, attachmentsSection.nextSibling);
                } else {
                    // Fallback: find Task Details section
                    const taskDetailsSection = document.querySelector('[data-section="Task Details"], [data-section="Subtask Details"]');
                    if (taskDetailsSection) {
                        taskDetailsSection.parentNode.insertBefore(subtasksContainer, taskDetailsSection.nextSibling);
                    }
                }
                
                subtasksContainer.style.display = 'block';
            });
        </script>
        @endif
    </div>
</x-filament-panels::page>
