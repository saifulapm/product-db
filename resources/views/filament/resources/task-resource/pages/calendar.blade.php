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
                    <div class="flex flex-col h-full w-full {{ !$isLast ? 'border-r border-gray-300 dark:border-gray-600' : '' }}" style="{{ !$isLast ? 'border-right: 1px solid rgb(209 213 219) !important;' : '' }}">
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
