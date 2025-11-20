<x-filament-panels::page>
    <style>
        /* Full screen modal styles */
        .fullscreen-calendar-modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            z-index: 99999 !important;
            background: white !important;
        }
        .fullscreen-calendar-modal[data-theme="dark"] {
            background: #111827 !important;
        }
        /* Hide Filament sidebar and header when modal is open */
        body.calendar-fullscreen-open .fi-sidebar,
        body.calendar-fullscreen-open .fi-topbar,
        body.calendar-fullscreen-open .fi-main-ctn {
            display: none !important;
        }
        body.calendar-fullscreen-open {
            overflow: hidden !important;
        }
    </style>
    
    @if($isFullScreen)
    <!-- Full Screen Calendar View -->
    <div 
        class="fullscreen-calendar-modal bg-white dark:bg-gray-900"
        x-data="{ isFullScreen: @entangle('isFullScreen') }"
        x-show="isFullScreen"
        x-cloak
        x-init="
            document.body.classList.add('calendar-fullscreen-open');
            $watch('isFullScreen', value => {
                if (!value) {
                    document.body.classList.remove('calendar-fullscreen-open');
                }
            });
        "
        x-effect="
            if (isFullScreen) {
                document.body.classList.add('calendar-fullscreen-open');
            } else {
                document.body.classList.remove('calendar-fullscreen-open');
            }
        "
    >
        <!-- Close Button -->
        <div class="absolute top-4 right-4" style="z-index: 100000;">
            <button
                wire:click="exitFullScreen"
                class="p-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-full transition-colors"
                title="Exit Full Screen"
            >
                <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Calendar Content - Full Screen -->
        <div class="h-full w-full flex flex-col p-6" style="height: 100vh; overflow-y: auto;">
            @include('filament.resources.task-resource.pages.partials.calendar-content', ['isFullScreen' => true])
        </div>
    </div>
    @endif
    
    <!-- Normal Calendar View -->
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
                <button 
                    wire:click="toggleFullScreen" 
                    type="button"
                    class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold shadow-lg flex items-center justify-center gap-2 transition-all border-2 border-green-700"
                    style="min-width: 140px; font-size: 14px;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <span class="font-bold">Full Screen</span>
                </button>
            </div>
            <div class="flex items-center gap-4 flex-wrap">
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
    
    <script>
        // Cleanup body class when modal closes - wait for Livewire to be available
        document.addEventListener('livewire:init', () => {
            Livewire.on('calendar-fullscreen-closed', () => {
                document.body.classList.remove('calendar-fullscreen-open');
            });
        });
        
        // Fallback if Livewire is already loaded
        if (typeof Livewire !== 'undefined') {
            Livewire.on('calendar-fullscreen-closed', () => {
                document.body.classList.remove('calendar-fullscreen-open');
            });
        }
    </script>
</x-filament-panels::page>
