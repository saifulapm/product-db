<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search and Sort Controls -->
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
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
        
        <!-- Incomplete Section -->
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Incomplete</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                @forelse($this->getIncompleteTasks() as $task)
                    <div 
                        class="bg-orange-50 dark:bg-orange-900/30 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 p-6 hover:shadow-md transition-shadow relative task-card" 
                        style="background-color: #fff7ed;"
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
                        <div class="mb-4">
                            <a href="{{ $this->getViewTaskUrl($task) }}" class="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline">
                                {{ $task->title }}
                            </a>
                        </div>
                        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Due Date: {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}
                        </div>
                        <div class="mb-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $this->getProgressPercentage($task) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-primary-600 dark:bg-primary-500 h-2 rounded-full transition-all duration-300" style="width: {{ $this->getProgressPercentage($task) }}%"></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            @if($task->subtasks->count() > 0)
                                {{ $task->subtasks->where('is_completed', true)->count() }} of {{ $task->subtasks->count() }} subtasks completed
                            @else
                                No subtasks
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No incomplete tasks
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Separator -->
        <div class="border-t border-gray-300 dark:border-gray-700 my-6"></div>

        <!-- Complete Section -->
        <div>
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Complete</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                @forelse($this->getCompleteTasks() as $task)
                    <div 
                        class="bg-green-50 dark:bg-green-900/30 rounded-lg shadow-sm border border-green-200 dark:border-green-800 p-6 hover:shadow-md transition-shadow relative task-card" 
                        style="background-color: #f0fdf4;"
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
                        <div class="mb-4">
                            <a href="{{ $this->getViewTaskUrl($task) }}" class="text-lg font-semibold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 underline">
                                {{ $task->title }}
                            </a>
                        </div>
                        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            Due Date: {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}
                        </div>
                        <div class="mb-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Progress</span>
                                <span class="text-xs font-medium text-success-600 dark:text-success-400">100%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-success-600 dark:bg-success-500 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            @if($task->subtasks->count() > 0)
                                {{ $task->subtasks->where('is_completed', true)->count() }} of {{ $task->subtasks->count() }} subtasks completed
                            @else
                                No subtasks
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

