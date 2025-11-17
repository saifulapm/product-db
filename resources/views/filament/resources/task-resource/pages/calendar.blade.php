<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Navigation, View Switcher and Filter -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
            <button 
                wire:click="previous" 
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                ← Previous
            </button>
            <div class="flex items-center gap-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getDateRange() }}</h2>
                <button 
                    wire:click="goToToday" 
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm"
                >
                    Today
                </button>
            </div>
            <div class="flex items-center gap-4">
                <!-- View Type Switcher -->
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button 
                        wire:click="setViewType('month')"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors {{ $viewType === 'month' ? 'bg-primary-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        Month
                    </button>
                    <button 
                        wire:click="setViewType('week')"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors {{ $viewType === 'week' ? 'bg-primary-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        Week
                    </button>
                    <button 
                        wire:click="setViewType('3day')"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors {{ $viewType === '3day' ? 'bg-primary-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        3 Day
                    </button>
                    <button 
                        wire:click="setViewType('day')"
                        class="px-3 py-1.5 text-sm font-medium rounded transition-colors {{ $viewType === 'day' ? 'bg-primary-600 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        Day
                    </button>
                </div>
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
                    wire:click="next" 
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    Next →
                </button>
            </div>
        </div>

        <!-- Month View -->
        @if($viewType === 'month')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-gray-300 dark:border-gray-600 overflow-hidden flex flex-col" style="height: calc(100vh - 250px);">
            <!-- Day Headers -->
            <div class="grid grid-cols-7 gap-0 border-b-2 border-gray-300 dark:border-gray-600 flex-shrink-0">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                    <div class="p-3 bg-gray-50 dark:bg-gray-900 text-center font-semibold text-sm text-gray-700 dark:text-gray-300 {{ !$loop->last ? 'border-r border-gray-300 dark:border-gray-600' : '' }}">
                        {{ $dayName }}
                    </div>
                @endforeach
            </div>
            <!-- Calendar Days Grid -->
            <div class="grid grid-cols-7 gap-0 flex-1 overflow-y-auto">
                @foreach($this->getMonthDays() as $dayData)
                    @php
                        $date = $dayData['date'];
                        $isCurrentMonth = $dayData['isCurrentMonth'];
                        $isToday = $dayData['isToday'];
                        $tasks = $this->getTasksForDate($date);
                        $isLastInRow = $loop->iteration % 7 === 0;
                    @endphp
                    <div class="flex flex-col min-h-[120px] border-r border-b border-gray-200 dark:border-gray-700 {{ !$isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900/50 opacity-60' : 'bg-white dark:bg-gray-800' }}">
                        <!-- Day Number -->
                        <div class="p-2 flex-shrink-0">
                            <span class="text-sm font-semibold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : ($isCurrentMonth ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500') }}">
                                {{ $date->day }}
                            </span>
                        </div>
                        <!-- Tasks -->
                        <div class="p-1 flex-1 overflow-y-auto">
                            <div class="space-y-1">
                                @foreach($tasks->take(3) as $task)
                                    @php
                                        $taskTitle = $task->title;
                                        $subtaskName = strpos($taskTitle, ' - ') !== false 
                                            ? trim(explode(' - ', $taskTitle)[0])
                                            : trim($taskTitle);
                                        
                                        $tagBgColor = match($subtaskName) {
                                            'Add Products' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                            'Size Grade or Thread Colors Needed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                            'Website Images' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                                            default => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        };
                                    @endphp
                                    <a 
                                        href="{{ $this->getViewTaskUrl($task) }}"
                                        class="block text-xs p-1 rounded truncate {{ $tagBgColor }} hover:opacity-80 transition-opacity"
                                        title="{{ $task->title }}"
                                    >
                                        {{ $subtaskName }}
                                    </a>
                                @endforeach
                                @if($tasks->count() > 3)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 p-1">
                                        +{{ $tasks->count() - 3 }} more
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Week View -->
        @if($viewType === 'week')
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
                            @include('filament.resources.task-resource.pages.partials.task-list', ['tasks' => $tasks])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- 3 Day View -->
        @if($viewType === '3day')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-gray-300 dark:border-gray-600 overflow-hidden" style="height: calc(100vh - 250px);">
            <div class="grid grid-cols-3 gap-0 h-full" style="display: grid; grid-template-columns: repeat(3, 1fr);">
                @foreach($this->getThreeDays() as $date)
                    @php
                        $isToday = $date->isToday();
                        $tasks = $this->getTasksForDate($date);
                        $isLast = $loop->last;
                    @endphp
                    <div class="flex flex-col h-full w-full {{ !$isLast ? 'border-r border-gray-300 dark:border-gray-600' : '' }}">
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
                        <div class="p-4 flex-1 bg-white dark:bg-gray-800 overflow-y-auto min-h-0">
                            @include('filament.resources.task-resource.pages.partials.task-list', ['tasks' => $tasks])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Day View -->
        @if($viewType === 'day')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border-2 border-gray-300 dark:border-gray-600 overflow-hidden" style="height: calc(100vh - 250px);">
            @php
                $date = $this->getSingleDay();
                $isToday = $date->isToday();
                $tasks = $this->getTasksForDate($date);
            @endphp
            <div class="flex flex-col h-full">
                <!-- Day Header -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900 border-b-2 border-gray-300 dark:border-gray-600 flex-shrink-0">
                    <div class="text-center">
                        <div class="text-lg font-medium text-gray-600 dark:text-gray-400 mb-2">
                            {{ $date->format('l') }}
                        </div>
                        <div class="text-4xl font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $date->day }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                            {{ $date->format('F Y') }}
                        </div>
                    </div>
                </div>
                <!-- Tasks Column -->
                <div class="p-6 flex-1 bg-white dark:bg-gray-800 overflow-y-auto">
                    @include('filament.resources.task-resource.pages.partials.task-list', ['tasks' => $tasks])
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
