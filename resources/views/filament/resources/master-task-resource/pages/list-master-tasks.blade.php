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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @forelse($this->getIncompleteTasks() as $task)
                    <div class="bg-orange-50 dark:bg-orange-900/30 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800 p-6 hover:shadow-md transition-shadow" style="background-color: #fff7ed;">
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @forelse($this->getCompleteTasks() as $task)
                    <div class="bg-green-50 dark:bg-green-900/30 rounded-lg shadow-sm border border-green-200 dark:border-green-800 p-6 hover:shadow-md transition-shadow" style="background-color: #f0fdf4;">
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
</x-filament-panels::page>

