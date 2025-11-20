<x-filament-panels::page>
    <style>
        .master-tasks-grid-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        .master-tasks-grid {
            display: grid !important;
            grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
        }
        @media (min-width: 640px) {
            .master-tasks-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 768px) {
            .master-tasks-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
        }
    </style>
    <div class="space-y-6 w-full max-w-full master-tasks-grid-container">
        <!-- Search and Sort Controls -->
        <div class="space-y-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search tasks by title..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
            </div>
            <div class="sm:w-64">
                <select
                    wire:model.live="sortBy"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                    <option value="due_date_asc">Deadline: Earliest First</option>
                    <option value="due_date_desc">Deadline: Latest First</option>
                    <option value="title_asc">Title: A-Z</option>
                    <option value="title_desc">Title: Z-A</option>
                </select>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Task Type
                    </label>
                    <select
                        wire:model.live="taskTypeFilter"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">All Task Types</option>
                        @foreach($this->getTaskTypeOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Assigned To
                    </label>
                    <select
                        wire:model.live="assigneeFilter"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">All Assignees</option>
                        @foreach($this->getAssigneeOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:w-64">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Status
                    </label>
                    <select
                        wire:model.live="statusFilter"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">All Tasks</option>
                        <option value="incomplete">Incomplete Only</option>
                        <option value="complete">Complete Only</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Incomplete Section -->
        @if(empty($statusFilter) || $statusFilter === 'incomplete')
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Incomplete</h2>
            <div class="space-y-4 w-full">
                @forelse($this->getIncompleteTasks() as $task)
                    @php
                        $projectName = $task->project->name ?? '';
                        $isProductAdditions = strtolower($projectName) === 'product additions';
                        $isWebsiteImages = strtolower($projectName) === 'website images';
                        
                        if ($isProductAdditions) {
                            $bgColor = 'bg-purple-50 dark:bg-purple-900/30';
                            $borderColor = 'border-purple-200 dark:border-purple-800';
                            $bgStyle = 'background-color: #faf5ff;';
                        } elseif ($isWebsiteImages) {
                            $bgColor = 'bg-blue-50 dark:bg-blue-900/30';
                            $borderColor = 'border-blue-200 dark:border-blue-800';
                            $bgStyle = 'background-color: #eff6ff;';
                        } else {
                            $bgColor = 'bg-orange-50 dark:bg-orange-900/30';
                            $borderColor = 'border-orange-200 dark:border-orange-800';
                            $bgStyle = 'background-color: #fff7ed;';
                        }
                    @endphp
                    <div 
                        class="{{ $bgColor }} rounded-lg shadow-sm border {{ $borderColor }} flex items-center justify-between p-4 hover:shadow-md transition-shadow relative task-card w-full" 
                        style="{{ $bgStyle }}"
                        data-task-id="{{ $task->id }}"
                        x-data="contextMenu()"
                        @contextmenu.prevent="positionMenu($event)"
                        @click.away="showMenu = false"
                    >
                        <!-- Context Menu -->
                        <div 
                            x-show="showMenu"
                            x-cloak
                            class="absolute z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 min-w-[120px]"
                            style="display: none; left: 0; top: 0;"
                            x-ref="contextMenu"
                            :style="`left: ${menuX}px; top: ${menuY}px;`"
                        >
                            <button 
                                wire:click="editTask({{ $task->id }})"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                        
                        <!-- List View Layout -->
                        <div class="flex items-center flex-1 min-w-0" style="gap: 6px;">
                            @if($task->assignedUser)
                                <div class="flex-shrink-0">
                                    @if($task->assignedUser->profile_picture)
                                        <div class="flex flex-col items-center justify-center min-w-[60px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 overflow-hidden">
                                            <img 
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($task->assignedUser->profile_picture) }}" 
                                                alt="{{ $task->assignedUser->name }}"
                                                class="w-full h-full object-cover"
                                                title="{{ $task->assignedUser->name }}"
                                            />
                                        </div>
                                    @else
                                        @php
                                            $initials = '';
                                            if ($task->assignedUser->first_name && $task->assignedUser->last_name) {
                                                $initials = strtoupper(substr($task->assignedUser->first_name, 0, 1) . substr($task->assignedUser->last_name, 0, 1));
                                            } else {
                                                $name = $task->assignedUser->name ?? '';
                                                $parts = explode(' ', trim($name));
                                                if (count($parts) >= 2) {
                                                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                                                } else {
                                                    $initials = strtoupper(substr($name, 0, 1));
                                                }
                                            }
                                        @endphp
                                        <div class="flex items-center justify-center min-w-[60px] px-3 py-2 aspect-square rounded-full border border-gray-300 dark:border-gray-600 bg-primary-100 dark:bg-primary-900">
                                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400 uppercase">{{ $initials }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @php
                                $earliestDueDate = null;
                                if ($task->subtasks->isNotEmpty()) {
                                    $earliestDueDate = $task->subtasks->whereNotNull('due_date')->min('due_date');
                                } elseif ($task->due_date) {
                                    $earliestDueDate = $task->due_date;
                                }
                            @endphp
                            @if($earliestDueDate)
                                <div class="flex-shrink-0">
                                    <div class="flex flex-col items-center justify-center min-w-[60px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            {{ \Carbon\Carbon::parse($earliestDueDate)->format('M') }}
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($earliestDueDate)->format('d') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="flex-1 flex flex-col justify-center">
                                <a href="{{ $this->getViewTaskUrl($task) }}" class="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline block truncate" style="margin-bottom: 10px;">
                                    {{ $task->title }}
                                </a>
                                @php
                                    $progressPercentage = $this->getProgressPercentage($task);
                                @endphp
                                <div class="flex-1 w-full">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5" style="max-width: 100%;">
                                        <div 
                                            class="h-1.5 rounded-full transition-all duration-300"
                                            style="width: {{ $progressPercentage }}%; background: linear-gradient(to right, #93c5fd, #3b82f6, #1e40af);"
                                        ></div>
                        </div>
                            </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            @if($task->subtasks->count() > 0)
                                <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }} subtasks
                                </div>
                            @endif
                            @php
                                $hasIncompleteSubtasks = $task->subtasks->where('is_completed', false)->count() > 0;
                                $confirmMessage = $hasIncompleteSubtasks 
                                    ? '⚠️ Warning: Not all subtasks are completed. Are you sure you want to mark this task as complete? All incomplete subtasks will be automatically marked as complete.'
                                    : 'Are you sure you want to mark this task as complete?';
                            @endphp
                            <button
                                wire:click="markTaskAsComplete({{ $task->id }})"
                                wire:confirm="{{ $confirmMessage }}"
                                class="flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 dark:border-gray-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors"
                                title="Mark as complete"
                            >
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No incomplete tasks
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- Separator -->
        @if(empty($statusFilter))
        <div class="border-t border-gray-300 dark:border-gray-700 my-6"></div>
        @endif

        <!-- Complete Section -->
        @if(empty($statusFilter) || $statusFilter === 'complete')
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Complete</h2>
            <div class="space-y-4 w-full">
                @forelse($this->getCompleteTasks() as $task)
                    <div 
                        class="bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-300 dark:border-gray-700 flex items-center justify-between p-4 hover:shadow-md transition-shadow relative task-card w-full" 
                        style="background-color: #f3f4f6;"
                        data-task-id="{{ $task->id }}"
                        x-data="contextMenu()"
                        @contextmenu.prevent="positionMenu($event)"
                        @click.away="showMenu = false"
                    >
                        <!-- Context Menu -->
                        <div 
                            x-show="showMenu"
                            x-cloak
                            class="absolute z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 min-w-[120px]"
                            style="display: none; left: 0; top: 0;"
                            x-ref="contextMenu"
                            :style="`left: ${menuX}px; top: ${menuY}px;`"
                        >
                            <button 
                                wire:click="editTask({{ $task->id }})"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:click="markTaskAsIncomplete({{ $task->id }})"
                                wire:confirm="Are you sure you want to mark this task as incomplete?"
                                class="w-full text-left px-4 py-2 text-sm text-orange-600 dark:text-orange-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Mark as Incomplete
                            </button>
                            <button 
                                wire:click="deleteTask({{ $task->id }})"
                                wire:confirm="Are you sure you want to delete this task?"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                        
                        <!-- List View Layout -->
                        <div class="flex items-center flex-1 min-w-0" style="gap: 6px;">
                            @if($task->assignedUser)
                                <div class="flex-shrink-0">
                                    @if($task->assignedUser->profile_picture)
                                        <div class="flex flex-col items-center justify-center min-w-[60px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 overflow-hidden h-full">
                                            <img 
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($task->assignedUser->profile_picture) }}" 
                                                alt="{{ $task->assignedUser->name }}"
                                                class="w-full h-full object-cover"
                                                title="{{ $task->assignedUser->name }}"
                                            />
                                        </div>
                                    @else
                                        @php
                                            $initials = '';
                                            if ($task->assignedUser->first_name && $task->assignedUser->last_name) {
                                                $initials = strtoupper(substr($task->assignedUser->first_name, 0, 1) . substr($task->assignedUser->last_name, 0, 1));
                                            } else {
                                                $name = $task->assignedUser->name ?? '';
                                                $parts = explode(' ', trim($name));
                                                if (count($parts) >= 2) {
                                                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                                                } else {
                                                    $initials = strtoupper(substr($name, 0, 1));
                                                }
                                            }
                                        @endphp
                                        <div class="flex items-center justify-center min-w-[60px] px-3 py-2 aspect-square rounded-full border border-gray-300 dark:border-gray-600 bg-primary-100 dark:bg-primary-900">
                                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400 uppercase">{{ $initials }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @php
                                $earliestDueDate = null;
                                if ($task->subtasks->isNotEmpty()) {
                                    $earliestDueDate = $task->subtasks->whereNotNull('due_date')->min('due_date');
                                } elseif ($task->due_date) {
                                    $earliestDueDate = $task->due_date;
                                }
                            @endphp
                            @if($earliestDueDate)
                                <div class="flex-shrink-0">
                                    <div class="flex flex-col items-center justify-center min-w-[60px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                            {{ \Carbon\Carbon::parse($earliestDueDate)->format('M') }}
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($earliestDueDate)->format('d') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <a href="{{ $this->getViewTaskUrl($task) }}" class="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline block truncate">
                                    {{ $task->title }}
                                </a>
                                <div class="mt-1 w-full max-w-[200px]">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div 
                                            class="h-1.5 rounded-full transition-all duration-300"
                                            style="width: 100%; background: linear-gradient(to right, #93c5fd, #3b82f6, #1e40af);"
                                        ></div>
                        </div>
                            </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            @if($task->subtasks->count() > 0)
                                <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }} subtasks
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No completed tasks
                    </div>
                @endforelse
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contextMenu', () => ({
                showMenu: false,
                menuX: 0,
                menuY: 0,
                positionMenu(event) {
                    this.showMenu = true;
                    const rect = event.currentTarget.getBoundingClientRect();
                    this.menuX = event.clientX - rect.left;
                    this.menuY = event.clientY - rect.top;
                    
                    // Adjust position on next tick after menu is rendered
                    this.$nextTick(() => {
                        const menu = this.$refs.contextMenu;
                        if (!menu) return;
                        
                        const menuRect = menu.getBoundingClientRect();
                        const viewportWidth = window.innerWidth;
                        const viewportHeight = window.innerHeight;
                        
                        let left = this.menuX;
                        let top = this.menuY;
                        
                        // Adjust if menu would go off screen
                        if (left + menuRect.width > viewportWidth) {
                            left = viewportWidth - menuRect.width - 10;
                        }
                        if (top + menuRect.height > viewportHeight) {
                            top = viewportHeight - menuRect.height - 10;
                        }
                        
                        menu.style.left = left + 'px';
                        menu.style.top = top + 'px';
                    });
                }
            }));
        });
    </script>
</x-filament-panels::page>

