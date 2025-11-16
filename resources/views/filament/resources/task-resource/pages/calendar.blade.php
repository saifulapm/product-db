<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Week Navigation and Filter -->
        <div class="flex items-center justify-between mb-6">
            <button 
                wire:click="previousWeek" 
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                ← Previous Week
            </button>
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getWeekRange() }}</h2>
                <button 
                    wire:click="goToToday" 
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm"
                >
                    Today
                </button>
            </div>
            <div class="flex items-center gap-4">
                <select 
                    wire:model.live="selectedAssignee"
                    class="px-4 py-2 pr-8 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors cursor-pointer"
                >
                    <option value="">All Assignees</option>
                    @foreach($this->getAssignees() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <button 
                    wire:click="nextWeek" 
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    Next Week →
                </button>
            </div>
        </div>

        <!-- Weekly Calendar Grid -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-gray-300 dark:border-gray-600 overflow-hidden" style="height: calc(100vh - 250px);">
            <div class="grid grid-cols-7 gap-0 h-full">
                @foreach($this->getWeekDays() as $date)
                    @php
                        $isToday = $date->isToday();
                        $tasks = $this->getTasksForDate($date);
                        $isLast = $loop->last;
                    @endphp
                    <div class="flex flex-col h-full {{ !$isLast ? 'border-r border-gray-300 dark:border-gray-600' : '' }}" style="{{ !$isLast ? 'border-right: 1px solid rgb(209 213 219) !important;' : '' }}">
                        <!-- Day Header -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-600 flex-shrink-0">
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $date->format('D') }}
                                </div>
                                <div class="text-2xl font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $date->day }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    {{ $date->format('M Y') }}
                                </div>
                            </div>
                        </div>

                        <!-- Tasks Column -->
                        <div class="p-3 flex-1 bg-white dark:bg-gray-800 overflow-y-auto">
                            <div class="space-y-2">
                                @foreach($tasks as $task)
                                    @php
                                        $isSubtask = !empty($task->parent_task_id);
                                        $isCompleted = $task->is_completed ?? false;
                                        $assignedUserName = $task->assignedUser->name ?? null;
                                        
                                        // Get task type tag name and color (matching TaskResource logic)
                                        $title = $task->title;
                                        $subtaskName = strpos($title, ' - ') !== false 
                                            ? trim(explode(' - ', $title)[0])
                                            : trim($title);
                                        
                                        $tagColor = match($subtaskName) {
                                            'Add Products' => 'warning', // orange/amber
                                            'Size Grade or Thread Colors Needed' => 'success', // green
                                            'Website Images' => 'purple', // purple
                                            default => 'success',
                                        };
                                        
                                        $tagBgColor = match($subtaskName) {
                                            'Add Products' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                            'Size Grade or Thread Colors Needed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'Website Images' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                            default => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        };
                                    @endphp
                                    <a 
                                        href="{{ $this->getViewTaskUrl($task) }}"
                                        class="block text-xs p-2 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow"
                                        title="{{ $task->title }}"
                                    >
                                        <div class="mb-1">
                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $tagBgColor }}" 
                                                @if($subtaskName === 'Add Products') style="background-color: #fed7aa !important; color: #9a3412 !important;" @endif>
                                                {{ $subtaskName }}
                                            </span>
                                        </div>
                                        @if($assignedUserName)
                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $assignedUserName }}
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
