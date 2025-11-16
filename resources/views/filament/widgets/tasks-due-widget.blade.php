<x-filament-widgets::widget>
    <div class="space-y-6">
        <!-- Overdue Tasks -->
        @php
            $overdueTasks = $this->getOverdueTasks();
        @endphp
        
        <x-filament::section>
            <x-slot name="heading">
                <span class="text-red-600 dark:text-red-400">Overdue ({{ $overdueTasks->count() }})</span>
            </x-slot>
            
            @if($overdueTasks->count() > 0)
                <div class="space-y-2">
                    @foreach($overdueTasks as $task)
                        <a 
                            href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task]) }}"
                            class="block p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $task->title }}
                                        </span>
                                        @if($task->parentTask)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $task->parentTask->title }})
                                            </span>
                                        @endif
                                    </div>
                                    @if($task->project)
                                        <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            {{ $task->project->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-red-600 dark:text-red-400 font-medium">
                                    {{ $task->due_date->format('M d, Y') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                    <p>No overdue tasks.</p>
                </div>
            @endif
        </x-filament::section>
        
        <!-- Tasks Due Today -->
        @php
            $tasksDueToday = $this->getTasksDueToday();
            $allTasksComplete = $this->areAllTasksComplete();
        @endphp
        
        <x-filament::section>
            <x-slot name="heading">
                Due Today ({{ $tasksDueToday->count() }})
            </x-slot>
            
            @if($allTasksComplete)
                <div class="text-center py-6">
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        All your tasks for the day are done - LFG 🥳
                    </p>
                </div>
            @elseif($tasksDueToday->count() > 0)
                <div class="space-y-2">
                    @foreach($tasksDueToday as $task)
                        <a 
                            href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task]) }}"
                            class="block p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $task->title }}
                                        </span>
                                        @if($task->parentTask)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $task->parentTask->title }})
                                            </span>
                                        @endif
                                    </div>
                                    @if($task->project)
                                        <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            {{ $task->project->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $task->due_date->format('g:i A') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                    <p>No tasks due today.</p>
                </div>
            @endif
        </x-filament::section>
        
        <!-- Upcoming Tasks -->
        @php
            $upcomingTasks = $this->getUpcomingTasks();
        @endphp
        
        <x-filament::section>
            <x-slot name="heading">
                Upcoming ({{ $upcomingTasks->count() }})
            </x-slot>
            
            @if($upcomingTasks->count() > 0)
                <div class="space-y-2">
                    @foreach($upcomingTasks as $task)
                        <a 
                            href="{{ \App\Filament\Resources\TaskResource::getUrl('view', ['record' => $task]) }}"
                            class="block p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $task->title }}
                                        </span>
                                        @if($task->parentTask)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $task->parentTask->title }})
                                            </span>
                                        @endif
                                    </div>
                                    @if($task->project)
                                        <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            {{ $task->project->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $task->due_date->format('M d, Y') }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                    <p>No upcoming tasks.</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>

